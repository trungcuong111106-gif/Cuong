using UnityEngine;
using System.Collections;

// ─── GOOMBA ───────────────────────────────────────────────────────────────────
public class Goomba : EnemyBase
{
    public GameObject flatSprite; // Sprite khi bị giẫm

    protected override void OnStomped()
    {
        GetComponent<Collider2D>().enabled = false;
        rb.linearVelocity = Vector2.zero;
        rb.bodyType = RigidbodyType2D.Static;
        if (flatSprite) flatSprite.SetActive(true);
        GetComponent<SpriteRenderer>().enabled = false;
        Destroy(gameObject, 0.4f);
    }

    protected override void OnKilledByFireball()
    {
        StartCoroutine(FlipAndDie());
    }

    IEnumerator FlipAndDie()
    {
        GetComponent<Collider2D>().enabled = false;
        rb.linearVelocity = new Vector2(0, 6f);
        transform.rotation = Quaternion.Euler(0, 0, 180f);
        yield return new WaitForSeconds(0.8f);
        Destroy(gameObject);
    }
}

// ─── KOOPA ────────────────────────────────────────────────────────────────────
public class Koopa : EnemyBase
{
    public float shellSpeed = 14f;
    private bool inShell;
    private bool shellMoving;

    protected override void OnStomped()
    {
        if (!inShell)
        {
            // Lần đầu giẫm → vào mai
            inShell = true;
            shellMoving = false;
            rb.linearVelocity = Vector2.zero;
            moveSpeed = 0;
            GetComponent<Animator>()?.SetBool("InShell", true);
        }
        else if (!shellMoving)
        {
            // Đá mai → mai lăn
            KickShell();
        }
        else
        {
            // Dừng mai đang lăn
            shellMoving = false;
            moveSpeed = 0;
            rb.linearVelocity = new Vector2(0, rb.linearVelocity.y);
        }
    }

    void KickShell()
    {
        shellMoving = true;
        // Hướng kick dựa trên vị trí người chơi
        float playerX = PlayerController.Instance?.transform.position.x ?? transform.position.x - 1f;
        direction = playerX < transform.position.x ? 1 : -1;
        moveSpeed = shellSpeed;
        AudioManager.Instance?.PlaySFX("kick");
    }

    protected override void OnKilledByFireball()
    {
        StartCoroutine(FlipAndDie());
    }

    IEnumerator FlipAndDie()
    {
        GetComponent<Collider2D>().enabled = false;
        rb.linearVelocity = new Vector2(0, 7f);
        transform.rotation = Quaternion.Euler(0, 0, 180f);
        yield return new WaitForSeconds(1f);
        Destroy(gameObject);
    }

    // Mai lăn tiêu diệt quái khác
    protected override void OnCollisionEnter2D(Collision2D col)
    {
        if (shellMoving && col.gameObject.CompareTag("Enemy"))
        {
            col.gameObject.GetComponent<EnemyBase>()?.KillByFireball();
            GameManager.Instance?.AddScore(200);
        }
        else
        {
            base.OnCollisionEnter2D(col);
        }
    }
}

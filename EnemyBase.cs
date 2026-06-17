using UnityEngine;

/// <summary>
/// Lớp cơ sở cho mọi kẻ địch.
/// Giẫm lên đầu → tiêu diệt; đụng người chơi từ bên → gây sát thương.
/// </summary>
[RequireComponent(typeof(Rigidbody2D), typeof(Collider2D))]
public abstract class EnemyBase : MonoBehaviour
{
    [Header("Enemy Base")]
    public float moveSpeed = 2f;
    public int scoreValue = 100;
    protected Rigidbody2D rb;
    protected bool isDead;
    protected int direction = -1; // -1 = trái

    protected virtual void Awake()
    {
        rb = GetComponent<Rigidbody2D>();
    }

    protected virtual void Update()
    {
        if (!isDead)
            rb.linearVelocity = new Vector2(moveSpeed * direction, rb.linearVelocity.y);
    }

    // Gọi khi Mario giẫm lên đầu
    public virtual void StompedByPlayer()
    {
        if (isDead) return;
        isDead = true;
        GameManager.Instance?.AddScore(scoreValue);
        AudioManager.Instance?.PlaySFX("stomp");
        OnStomped();
    }

    // Gọi khi bị bắn lửa
    public virtual void KillByFireball()
    {
        if (isDead) return;
        isDead = true;
        GameManager.Instance?.AddScore(scoreValue);
        AudioManager.Instance?.PlaySFX("kick");
        OnKilledByFireball();
    }

    // Gọi khi va chạm bên người chơi
    protected virtual void OnCollisionEnter2D(Collision2D col)
    {
        if (isDead) return;

        if (col.gameObject.CompareTag("Player"))
        {
            var player = col.gameObject.GetComponent<PlayerController>();
            if (player == null || player.IsDead) return;

            // Kiểm tra Mario có đang rơi xuống đầu quái không
            float relativeY = col.transform.position.y - transform.position.y;
            bool stompedFromAbove = relativeY > 0.3f && col.GetComponent<Rigidbody2D>()?.linearVelocity.y < -0.5f;

            if (stompedFromAbove)
            {
                StompedByPlayer();
                // Bật Mario lên sau khi giẫm
                col.GetComponent<Rigidbody2D>().linearVelocity = new Vector2(
                    col.GetComponent<Rigidbody2D>().linearVelocity.x, 8f);
            }
            else if (!player.IsInvincible)
            {
                player.TakeDamage();
            }
        }
        else if (col.gameObject.CompareTag("Wall") || col.gameObject.CompareTag("Enemy"))
        {
            direction *= -1; // Đổi chiều khi gặp tường hoặc quái khác
        }
    }

    protected abstract void OnStomped();
    protected abstract void OnKilledByFireball();

    protected void TurnAround() => direction *= -1;
}

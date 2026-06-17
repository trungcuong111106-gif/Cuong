using UnityEngine;
using System.Collections;

public enum PowerupType { Mushroom, FireFlower, Star, Coin1UP }

// ─── BASE POWERUP ─────────────────────────────────────────────────────────────
public class PowerupItem : MonoBehaviour
{
    public PowerupType type;
    protected bool collected;

    public virtual void Collect(PlayerController player)
    {
        if (collected) return;
        collected = true;
        player.ApplyPowerup(type);
        GameManager.Instance?.AddScore(1000);
        Destroy(gameObject);
    }
}

// ─── QUESTION BLOCK ───────────────────────────────────────────────────────────
/// <summary>
/// Khối "?" – húc đầu từ bên dưới để lấy vật phẩm.
/// </summary>
public class QuestionBlock : MonoBehaviour
{
    public GameObject spawnPrefab; // Nấm / Hoa / Sao
    public bool isEmpty;
    private Animator animator;

    void Awake() => animator = GetComponent<Animator>();

    void OnCollisionEnter2D(Collision2D col)
    {
        if (isEmpty) return;

        if (col.gameObject.CompareTag("Player"))
        {
            // Kiểm tra đầu người chơi đập từ bên dưới
            float relY = col.transform.position.y - transform.position.y;
            if (relY < -0.4f)
            {
                SpawnItem();
            }
        }
    }

    void SpawnItem()
    {
        isEmpty = true;
        animator?.SetBool("IsEmpty", true);
        AudioManager.Instance?.PlaySFX("blockHit");

        if (spawnPrefab != null)
        {
            var item = Instantiate(spawnPrefab,
                transform.position + Vector3.up * 0.5f, Quaternion.identity);
            item.GetComponent<PowerupRise>()?.StartRise(transform.position + Vector3.up);
        }

        StartCoroutine(BumpAnimation());
    }

    IEnumerator BumpAnimation()
    {
        Vector3 orig = transform.position;
        transform.position += Vector3.up * 0.15f;
        yield return new WaitForSeconds(0.1f);
        transform.position = orig;
    }
}

/// <summary>Hiệu ứng vật phẩm nổi lên từ khối.</summary>
public class PowerupRise : MonoBehaviour
{
    private Vector3 target;
    private bool rising;
    public float riseSpeed = 2f;

    public void StartRise(Vector3 targetPos)
    {
        target = targetPos;
        rising = true;
        GetComponent<Collider2D>().enabled = false;
    }

    void Update()
    {
        if (!rising) return;
        transform.position = Vector3.MoveTowards(transform.position, target, riseSpeed * Time.deltaTime);
        if (Vector3.Distance(transform.position, target) < 0.05f)
        {
            rising = false;
            GetComponent<Collider2D>().enabled = true;
            // Nấm: tự di chuyển sang bên
            GetComponent<Rigidbody2D>()?.AddForce(Vector2.right * 2f, ForceMode2D.Impulse);
        }
    }
}

// ─── COIN PICKUP ──────────────────────────────────────────────────────────────
public class CoinPickup : MonoBehaviour
{
    public int value = 1;
    private bool collected;

    public void Collect()
    {
        if (collected) return;
        collected = true;
        GameManager.Instance?.CollectCoin(value);
        AudioManager.Instance?.PlaySFX("coin");
        // Hiệu ứng coin bay lên
        StartCoroutine(CollectAnimation());
    }

    IEnumerator CollectAnimation()
    {
        GetComponent<Collider2D>().enabled = false;
        float t = 0;
        Vector3 start = transform.position;
        while (t < 0.4f)
        {
            transform.position = start + Vector3.up * t * 5f;
            t += Time.deltaTime;
            yield return null;
        }
        Destroy(gameObject);
    }
}

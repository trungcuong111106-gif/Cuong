using UnityEngine;

public class FireballSpawner : MonoBehaviour
{
    public static FireballSpawner Instance { get; private set; }
    public GameObject fireballPrefab;

    void Awake() => Instance = this;

    public void Spawn(Vector2 origin, bool facingRight)
    {
        var fb = Instantiate(fireballPrefab, origin + Vector2.up * 0.3f, Quaternion.identity);
        fb.GetComponent<Fireball>()?.Init(facingRight);
    }
}

/// <summary>
/// Viên đạn lửa: bay thẳng, nảy trên sàn, tiêu diệt quái.
/// </summary>
[RequireComponent(typeof(Rigidbody2D))]
public class Fireball : MonoBehaviour
{
    public float speed = 12f;
    public float bounceForce = 6f;
    public LayerMask groundLayer;
    public LayerMask enemyLayer;

    private Rigidbody2D rb;
    private int direction;
    private bool active = true;

    void Awake() => rb = GetComponent<Rigidbody2D>();

    public void Init(bool facingRight)
    {
        direction = facingRight ? 1 : -1;
        rb.linearVelocity = new Vector2(speed * direction, -3f);
    }

    void OnCollisionEnter2D(Collision2D col)
    {
        if (!active) return;

        if (((1 << col.gameObject.layer) & groundLayer) != 0)
        {
            // Nảy lên
            rb.linearVelocity = new Vector2(speed * direction, bounceForce);
        }
        else if (((1 << col.gameObject.layer) & enemyLayer) != 0)
        {
            col.gameObject.GetComponent<EnemyBase>()?.KillByFireball();
            Explode();
        }
        else
        {
            Explode();
        }
    }

    void Explode()
    {
        active = false;
        // Play explosion anim then destroy
        Destroy(gameObject, 0.1f);
    }

    void Update()
    {
        // Tự hủy nếu ra ngoài màn hình
        if (Mathf.Abs(transform.position.x - Camera.main.transform.position.x) > 20f)
            Destroy(gameObject);
    }
}

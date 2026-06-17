using UnityEngine;
using System.Collections;
using TMPro;

/// <summary>
/// Coin bay lên màn hình kiểu Mario – hiệu ứng đồ họa khi nhặt xu.
/// Tách riêng để dễ tuỳ chỉnh particle / animation.
/// </summary>
public class CoinEffect : MonoBehaviour
{
    public static CoinEffect Instance { get; private set; }

    public GameObject coinVFXPrefab;   // Prefab coin vàng nhỏ bay lên
    public Transform coinUITarget;     // Vị trí đồng xu trên HUD (để coin bay về đó)

    void Awake() => Instance = this;

    public void SpawnCoinVFX(Vector3 worldPos)
    {
        if (coinVFXPrefab == null) return;
        var vfx = Instantiate(coinVFXPrefab, worldPos, Quaternion.identity);
        StartCoroutine(AnimateCoinToUI(vfx.transform));
    }

    IEnumerator AnimateCoinToUI(Transform coin)
    {
        Vector3 start = coin.position;
        float t = 0;
        float duration = 0.6f;

        // Bay lên trước
        Vector3 peak = start + Vector3.up * 2f;
        while (t < duration * 0.3f)
        {
            coin.position = Vector3.Lerp(start, peak, t / (duration * 0.3f));
            t += Time.deltaTime;
            yield return null;
        }

        // Sau đó bay về UI
        if (coinUITarget != null)
        {
            Vector3 uiWorldPos = Camera.main.ScreenToWorldPoint(
                new Vector3(coinUITarget.position.x, coinUITarget.position.y, 10f));
            t = 0;
            Vector3 currentPos = coin.position;
            while (t < duration * 0.7f)
            {
                coin.position = Vector3.Lerp(currentPos, uiWorldPos, t / (duration * 0.7f));
                coin.localScale = Vector3.Lerp(Vector3.one, Vector3.zero, t / (duration * 0.7f));
                t += Time.deltaTime;
                yield return null;
            }
        }

        Destroy(coin.gameObject);
    }
}

/// <summary>
/// Riser – vật phẩm bổ trợ nổi lên từ khối, hỗ trợ nấm tự chạy sang bên.
/// </summary>
public class ItemRiser : MonoBehaviour
{
    public float riseDistance = 1f;
    public float riseSpeed = 2f;
    public bool runAfterRise = true;   // Nấm tự chạy; hoa đứng yên
    public float runSpeed = 2f;

    private bool rising = true;
    private Vector3 startPos;
    private Rigidbody2D rb;

    void Awake()
    {
        startPos = transform.position;
        rb = GetComponent<Rigidbody2D>();
        if (rb) rb.gravityScale = 0; // Tắt trọng lực khi đang nổi lên
        GetComponent<Collider2D>().enabled = false;
    }

    void Update()
    {
        if (!rising) return;
        transform.position = Vector3.MoveTowards(
            transform.position, startPos + Vector3.up * riseDistance, riseSpeed * Time.deltaTime);

        if (Vector3.Distance(transform.position, startPos + Vector3.up * riseDistance) < 0.02f)
        {
            rising = false;
            GetComponent<Collider2D>().enabled = true;
            if (rb)
            {
                rb.gravityScale = 1f;
                if (runAfterRise)
                    rb.linearVelocity = new Vector2(runSpeed, 0);
            }
        }
    }

    void OnCollisionEnter2D(Collision2D col)
    {
        if (!rising && runAfterRise && col.gameObject.CompareTag("Wall"))
        {
            runSpeed *= -1;
            rb.linearVelocity = new Vector2(runSpeed, rb.linearVelocity.y);
        }
    }
}

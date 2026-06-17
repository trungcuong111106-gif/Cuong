using UnityEngine;
using System.Collections;

/// <summary>
/// Camera theo nhân vật mượt mà, giới hạn theo biên độ màn,
/// chỉ cuộn về phía trước (không cuộn lùi như Mario gốc), hỗ trợ rung camera.
/// </summary>
public class CameraController : MonoBehaviour
{
    public static CameraController Instance { get; private set; }

    [Header("Target")]
    public Transform target;
    public Vector3 offset = new Vector3(2f, 1f, -10f);

    [Header("Smoothing")]
    public float smoothX = 8f;
    public float smoothY = 5f;
    public bool lockY = false;
    public float lockedY = 0f;

    [Header("Bounds")]
    public float minX = -5f;
    public float maxX = 500f;
    public float minY = -10f;
    public float maxY = 20f;

    [Header("No-scroll-back")]
    public bool noScrollBack = true;   // Camera không cuộn lùi như Mario gốc
    private float furthestX;

    // Shake
    private float shakeDuration;
    private float shakeMagnitude;

    private Camera cam;

    void Awake()
    {
        Instance = this;
        cam = GetComponent<Camera>();
    }

    void Start()
    {
        if (target != null)
            furthestX = target.position.x;
    }

    void LateUpdate()
    {
        if (target == null) return;

        float desiredX = target.position.x + offset.x;
        float desiredY = lockY ? lockedY : target.position.y + offset.y;

        // Không cuộn lùi
        if (noScrollBack)
        {
            furthestX = Mathf.Max(furthestX, desiredX);
            desiredX = furthestX;
        }

        float newX = Mathf.Lerp(transform.position.x, desiredX, smoothX * Time.deltaTime);
        float newY = Mathf.Lerp(transform.position.y, desiredY, smoothY * Time.deltaTime);

        // Giới hạn biên
        newX = Mathf.Clamp(newX, minX, maxX);
        newY = Mathf.Clamp(newY, minY, maxY);

        // Rung camera
        Vector3 shake = Vector3.zero;
        if (shakeDuration > 0)
        {
            shake = Random.insideUnitSphere * shakeMagnitude;
            shake.z = 0;
            shakeDuration -= Time.deltaTime;
        }

        transform.position = new Vector3(newX, newY, offset.z) + shake;
    }

    public void Shake(float duration = 0.3f, float magnitude = 0.15f)
    {
        shakeDuration = duration;
        shakeMagnitude = magnitude;
    }

    // Gọi khi bắt đầu màn để set bounds từ Tilemap
    public void SetBounds(float minXVal, float maxXVal, float minYVal, float maxYVal)
    {
        minX = minXVal; maxX = maxXVal;
        minY = minYVal; maxY = maxYVal;
    }

    // Zoom hiệu ứng (ví dụ: lúc qua cổng)
    public IEnumerator ZoomTo(float targetSize, float duration)
    {
        float startSize = cam.orthographicSize;
        float t = 0;
        while (t < duration)
        {
            cam.orthographicSize = Mathf.Lerp(startSize, targetSize, t / duration);
            t += Time.deltaTime;
            yield return null;
        }
        cam.orthographicSize = targetSize;
    }
}

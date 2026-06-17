using UnityEngine;

/// <summary>
/// Xử lý animation nâng cao cho Mario:
/// - Sprite thay đổi theo trạng thái (Small / Big / Fire)
/// - Hiệu ứng biến hình (grow/shrink flicker)
/// - Squash & Stretch khi nhảy/đáp xuống
/// </summary>
[RequireComponent(typeof(Animator), typeof(SpriteRenderer))]
public class PlayerAnimator : MonoBehaviour
{
    [Header("Sprite Sets")]
    public RuntimeAnimatorController smallController;
    public RuntimeAnimatorController bigController;
    public RuntimeAnimatorController fireController;

    [Header("Squash & Stretch")]
    public bool useSquashStretch = true;
    public float squashAmount  = 0.7f;
    public float stretchAmount = 1.3f;
    public float squashSpeed   = 10f;

    private Animator anim;
    private SpriteRenderer sr;
    private Rigidbody2D rb;
    private PlayerController controller;

    private PlayerState lastState;
    private Vector3 targetScale = Vector3.one;
    private bool wasGrounded;

    void Awake()
    {
        anim       = GetComponent<Animator>();
        sr         = GetComponent<SpriteRenderer>();
        rb         = GetComponent<Rigidbody2D>();
        controller = GetComponent<PlayerController>();
    }

    void Update()
    {
        UpdateAnimatorController();
        HandleSquashStretch();
    }

    void UpdateAnimatorController()
    {
        if (controller.currentState == lastState) return;
        lastState = controller.currentState;

        switch (lastState)
        {
            case PlayerState.Small:
                if (smallController) anim.runtimeAnimatorController = smallController;
                break;
            case PlayerState.Big:
                if (bigController) anim.runtimeAnimatorController = bigController;
                StartCoroutine(GrowEffect());
                break;
            case PlayerState.Fire:
                if (fireController) anim.runtimeAnimatorController = fireController;
                StartCoroutine(GrowEffect());
                break;
        }
    }

    System.Collections.IEnumerator GrowEffect()
    {
        // Nhấp nháy màu trong 1 giây khi đổi state
        float t = 0;
        while (t < 1f)
        {
            sr.color = t % 0.1f < 0.05f ? Color.white : Color.grey;
            t += Time.deltaTime;
            yield return null;
        }
        sr.color = Color.white;
    }

    void HandleSquashStretch()
    {
        if (!useSquashStretch) return;

        bool isGrounded = controller != null &&
            Physics2D.OverlapCircle(transform.position + Vector3.down * 0.5f,
                0.15f, LayerMask.GetMask("Ground"));

        // Khi đáp xuống đất → Squash
        if (isGrounded && !wasGrounded && rb.linearVelocity.y < -3f)
        {
            float impact = Mathf.Clamp01(Mathf.Abs(rb.linearVelocity.y) / 20f);
            targetScale = new Vector3(1f + impact * 0.3f, squashAmount - impact * 0.15f, 1f);
        }
        // Khi nhảy lên → Stretch
        else if (!isGrounded && rb.linearVelocity.y > 3f)
        {
            targetScale = new Vector3(0.85f, stretchAmount, 1f);
        }
        // Bình thường
        else if (isGrounded)
        {
            targetScale = Vector3.one;
        }

        transform.localScale = Vector3.Lerp(transform.localScale, targetScale, squashSpeed * Time.deltaTime);
        wasGrounded = isGrounded;
    }
}

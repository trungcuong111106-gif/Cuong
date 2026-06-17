using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

/// <summary>
/// Input Manager hỗ trợ cả bàn phím (PC) và nút cảm ứng (Mobile).
/// Tạo virtual joystick + nút nhảy/chạy/bắn trên màn hình.
/// </summary>
public class InputManager : MonoBehaviour
{
    public static InputManager Instance { get; private set; }

    [Header("Mobile UI")]
    public GameObject mobileControls;   // Panel chứa nút mobile
    public FloatingJoystick joystick;   // Joystick ảo (Asset: Feel / Custom)
    public Button jumpButton;
    public Button runButton;
    public Button fireButton;

    // Trạng thái input
    public float Horizontal { get; private set; }
    public bool Jump { get; private set; }
    public bool JumpHeld { get; private set; }
    public bool Run { get; private set; }
    public bool Fire { get; private set; }

    // Mobile button states
    private bool mobileJump;
    private bool mobileJumpHeld;
    private bool mobileRun;
    private bool mobileFire;

    private bool isMobile;

    void Awake()
    {
        Instance = this;
        // Phát hiện nền tảng
        isMobile = Application.isMobilePlatform
#if UNITY_EDITOR
            || false  // Đổi thành true để test mobile UI trong Editor
#endif
        ;
        mobileControls?.SetActive(isMobile);
    }

    void Start()
    {
        if (!isMobile) return;

        // Gán sự kiện nút mobile
        SetupMobileButton(jumpButton,
            onDown: () => { mobileJump = true; mobileJumpHeld = true; },
            onUp:   () => { mobileJump = false; mobileJumpHeld = false; });

        SetupMobileButton(runButton,
            onDown: () => mobileRun = true,
            onUp:   () => mobileRun = false);

        SetupMobileButton(fireButton,
            onDown: () => mobileFire = true,
            onUp:   () => mobileFire = false);
    }

    void Update()
    {
        if (isMobile)
        {
            Horizontal = joystick != null ? joystick.Horizontal : 0f;
            Jump       = mobileJump;
            JumpHeld   = mobileJumpHeld;
            Run        = mobileRun;
            Fire       = mobileFire;
            // Reset one-frame press
            mobileJump = false;
        }
        else
        {
            Horizontal = Input.GetAxisRaw("Horizontal");
            Jump       = Input.GetButtonDown("Jump");
            JumpHeld   = Input.GetButton("Jump");
            Run        = Input.GetButton("Fire1");  // Left Shift / Z
            Fire       = Input.GetButtonDown("Fire2"); // X / Space
        }

        // Bắn lửa
        if (Fire && PlayerController.Instance?.currentState == PlayerState.Fire)
            PlayerController.Instance.ShootFireball();
    }

    // Helper để gán EventTrigger cho nút mobile
    void SetupMobileButton(Button btn, System.Action onDown, System.Action onUp)
    {
        if (btn == null) return;
        var trigger = btn.gameObject.GetComponent<EventTrigger>()
                   ?? btn.gameObject.AddComponent<EventTrigger>();

        var down = new EventTrigger.Entry { eventID = EventTriggerType.PointerDown };
        down.callback.AddListener(_ => onDown());
        trigger.triggers.Add(down);

        var up = new EventTrigger.Entry { eventID = EventTriggerType.PointerUp };
        up.callback.AddListener(_ => onUp());
        trigger.triggers.Add(up);
    }
}

// ─── Simple Floating Joystick ─────────────────────────────────────────────────
/// <summary>
/// Joystick ảo đơn giản không cần asset ngoài.
/// Gắn vào một RectTransform làm vùng cảm ứng.
/// </summary>
public class FloatingJoystick : MonoBehaviour, IPointerDownHandler, IDragHandler, IPointerUpHandler
{
    public float handleRange = 1f;
    public float deadZone = 0.1f;

    public RectTransform background;
    public RectTransform handle;

    public float Horizontal { get; private set; }
    public float Vertical   { get; private set; }

    private RectTransform rectTransform;
    private Canvas canvas;
    private Camera cam;

    void Awake()
    {
        rectTransform = GetComponent<RectTransform>();
        canvas = GetComponentInParent<Canvas>();
        cam = canvas.renderMode == RenderMode.ScreenSpaceCamera ? canvas.worldCamera : null;
        background.gameObject.SetActive(false);
    }

    public void OnPointerDown(PointerEventData eventData)
    {
        background.anchoredPosition = ScreenToAnchoredPosition(eventData.position);
        background.gameObject.SetActive(true);
        OnDrag(eventData);
    }

    public void OnDrag(PointerEventData eventData)
    {
        RectTransformUtility.ScreenPointToLocalPointInRectangle(
            background, eventData.position, cam, out Vector2 pos);

        pos /= background.sizeDelta * 0.5f;
        Vector2 clampedPos = Vector2.ClampMagnitude(pos, handleRange);
        handle.anchoredPosition = clampedPos * background.sizeDelta * 0.5f;

        Horizontal = Mathf.Abs(clampedPos.x) > deadZone ? clampedPos.x : 0;
        Vertical   = Mathf.Abs(clampedPos.y) > deadZone ? clampedPos.y : 0;
    }

    public void OnPointerUp(PointerEventData eventData)
    {
        Horizontal = 0; Vertical = 0;
        handle.anchoredPosition = Vector2.zero;
        background.gameObject.SetActive(false);
    }

    Vector2 ScreenToAnchoredPosition(Vector2 screenPos)
    {
        RectTransformUtility.ScreenPointToLocalPointInRectangle(
            rectTransform, screenPos, cam, out Vector2 localPos);
        Vector2 pivotOffset = rectTransform.pivot * rectTransform.sizeDelta;
        return localPos - background.anchorMax * rectTransform.sizeDelta + pivotOffset;
    }
}

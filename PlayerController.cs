using UnityEngine;

/// <summary>
/// Điều khiển nhân vật Mario: di chuyển có quán tính, nhảy lấy đà, va chạm.
/// </summary>
[RequireComponent(typeof(Rigidbody2D), typeof(Collider2D))]
public class PlayerController : MonoBehaviour
{
    [Header("Movement")]
    public float walkSpeed = 5f;
    public float runSpeed = 9f;
    public float acceleration = 15f;
    public float deceleration = 20f;

    [Header("Jump")]
    public float jumpForce = 14f;
    public float runJumpBonus = 3f;      // Nhảy cao hơn khi đang chạy
    public float runJumpDistanceBonus = 2f; // Tốc độ ngang khi nhảy sau khi chạy
    public float fallMultiplier = 2.5f;
    public float lowJumpMultiplier = 2f;
    public LayerMask groundLayer;
    public Transform groundCheck;
    public float groundCheckRadius = 0.15f;

    [Header("State")]
    public PlayerState currentState = PlayerState.Small;

    // Internal
    private Rigidbody2D rb;
    private Animator animator;
    private SpriteRenderer spriteRenderer;

    private float horizontalInput;
    private bool isGrounded;
    private bool isRunning;
    private bool isFacingRight = true;
    private bool isInvincible;
    private float invincibleTimer;
    private bool isDead;

    private float currentSpeed;

    public static PlayerController Instance { get; private set; }

    void Awake()
    {
        Instance = this;
        rb = GetComponent<Rigidbody2D>();
        animator = GetComponent<Animator>();
        spriteRenderer = GetComponent<SpriteRenderer>();
    }

    void Update()
    {
        if (isDead) return;

        HandleInput();
        HandleInvincibility();
        UpdateAnimator();
    }

    void FixedUpdate()
    {
        if (isDead) return;

        CheckGround();
        HandleMovement();
        HandleBetterJump();
    }

    void HandleInput()
    {
        horizontalInput = Input.GetAxisRaw("Horizontal");
        isRunning = Input.GetButton("Fire1"); // Shift/Z để chạy

        if (Input.GetButtonDown("Jump") && isGrounded)
            Jump();
    }

    void CheckGround()
    {
        isGrounded = Physics2D.OverlapCircle(groundCheck.position, groundCheckRadius, groundLayer);
    }

    void HandleMovement()
    {
        float targetSpeed = horizontalInput * (isRunning ? runSpeed : walkSpeed);
        float rate = (Mathf.Abs(horizontalInput) > 0.01f) ? acceleration : deceleration;

        currentSpeed = Mathf.MoveTowards(rb.linearVelocity.x, targetSpeed, rate * Time.fixedDeltaTime);
        rb.linearVelocity = new Vector2(currentSpeed, rb.linearVelocity.y);

        // Flip sprite
        if (horizontalInput > 0 && !isFacingRight) Flip();
        else if (horizontalInput < 0 && isFacingRight) Flip();
    }

    void Jump()
    {
        float speed = Mathf.Abs(rb.linearVelocity.x);
        float jumpY = jumpForce;
        float jumpX = rb.linearVelocity.x;

        // Lấy đà chạy → nhảy cao & xa hơn
        if (isRunning && speed > walkSpeed * 0.8f)
        {
            jumpY += runJumpBonus;
            jumpX += Mathf.Sign(rb.linearVelocity.x) * runJumpDistanceBonus;
        }

        rb.linearVelocity = new Vector2(jumpX, jumpY);
        AudioManager.Instance?.PlaySFX("jump");
    }

    void HandleBetterJump()
    {
        // Rơi nhanh hơn (cảm giác nặng hơn khi rơi)
        if (rb.linearVelocity.y < 0)
        {
            rb.linearVelocity += Vector2.up * Physics2D.gravity.y * (fallMultiplier - 1) * Time.fixedDeltaTime;
        }
        // Nhả nút nhảy sớm → nhảy thấp hơn
        else if (rb.linearVelocity.y > 0 && !Input.GetButton("Jump"))
        {
            rb.linearVelocity += Vector2.up * Physics2D.gravity.y * (lowJumpMultiplier - 1) * Time.fixedDeltaTime;
        }
    }

    void Flip()
    {
        isFacingRight = !isFacingRight;
        spriteRenderer.flipX = !isFacingRight;
    }

    // ─── Power-up ─────────────────────────────────────────────────────────────

    public void ApplyPowerup(PowerupType type)
    {
        switch (type)
        {
            case PowerupType.Mushroom:
                if (currentState == PlayerState.Small)
                {
                    currentState = PlayerState.Big;
                    transform.localScale = new Vector3(1f, 2f, 1f);
                    AudioManager.Instance?.PlaySFX("powerup");
                }
                break;

            case PowerupType.FireFlower:
                currentState = PlayerState.Fire;
                AudioManager.Instance?.PlaySFX("powerup");
                break;

            case PowerupType.Star:
                StartCoroutine(StarInvincibility());
                break;
        }
    }

    System.Collections.IEnumerator StarInvincibility()
    {
        isInvincible = true;
        AudioManager.Instance?.PlayMusic("starTheme");
        float elapsed = 0f;
        float duration = 10f;
        while (elapsed < duration)
        {
            spriteRenderer.enabled = !spriteRenderer.enabled;
            yield return new WaitForSeconds(0.1f);
            elapsed += 0.1f;
        }
        spriteRenderer.enabled = true;
        isInvincible = false;
        AudioManager.Instance?.PlayMusic("levelTheme");
    }

    void HandleInvincibility()
    {
        if (isInvincible && invincibleTimer > 0)
        {
            invincibleTimer -= Time.deltaTime;
            // nhấp nháy
            spriteRenderer.enabled = Mathf.Sin(invincibleTimer * 20) > 0;
            if (invincibleTimer <= 0)
            {
                isInvincible = false;
                spriteRenderer.enabled = true;
            }
        }
    }

    // ─── Va chạm với kẻ địch / nguy hiểm ────────────────────────────────────

    public void TakeDamage()
    {
        if (isInvincible || isDead) return;

        if (currentState == PlayerState.Fire || currentState == PlayerState.Big)
        {
            currentState = PlayerState.Small;
            transform.localScale = Vector3.one;
            isInvincible = true;
            invincibleTimer = 2f;
            AudioManager.Instance?.PlaySFX("pipe"); // shrink sound
        }
        else
        {
            Die();
        }
    }

    void Die()
    {
        isDead = true;
        rb.linearVelocity = new Vector2(0, 10f);
        rb.gravityScale = 3f;
        GetComponent<Collider2D>().enabled = false;
        AudioManager.Instance?.PlaySFX("die");
        GameManager.Instance?.OnPlayerDied();
    }

    // ─── Bắn lửa ──────────────────────────────────────────────────────────────

    public void ShootFireball()
    {
        if (currentState != PlayerState.Fire) return;
        FireballSpawner.Instance?.Spawn(transform.position, isFacingRight);
        AudioManager.Instance?.PlaySFX("fireball");
    }

    void UpdateAnimator()
    {
        if (animator == null) return;
        animator.SetFloat("Speed", Mathf.Abs(rb.linearVelocity.x));
        animator.SetBool("IsGrounded", isGrounded);
        animator.SetFloat("VelocityY", rb.linearVelocity.y);
        animator.SetBool("IsRunning", isRunning && Mathf.Abs(rb.linearVelocity.x) > walkSpeed * 0.9f);
        animator.SetInteger("State", (int)currentState);
    }

    // ─── Trigger ──────────────────────────────────────────────────────────────

    void OnTriggerEnter2D(Collider2D col)
    {
        if (col.CompareTag("Coin"))
        {
            col.GetComponent<CoinPickup>()?.Collect();
        }
        else if (col.CompareTag("Powerup"))
        {
            col.GetComponent<PowerupItem>()?.Collect(this);
        }
        else if (col.CompareTag("GoalFlag"))
        {
            GameManager.Instance?.LevelComplete();
        }
        else if (col.CompareTag("Checkpoint"))
        {
            GameManager.Instance?.SetCheckpoint(col.transform.position);
        }
        else if (col.CompareTag("Hazard") && !isInvincible)
        {
            TakeDamage();
        }
    }

    public bool IsInvincible => isInvincible;
    public bool IsDead => isDead;
}

public enum PlayerState { Small, Big, Fire }

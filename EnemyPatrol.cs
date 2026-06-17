using UnityEngine;

/// <summary>
/// AI tuần tra nâng cao cho kẻ địch:
/// - Phát hiện người chơi trong tầm nhìn → đuổi theo
/// - Dừng tại mép sàn (không rơi xuống)
/// - Quay đầu khi gặp tường
/// </summary>
[RequireComponent(typeof(EnemyBase))]
public class EnemyPatrol : MonoBehaviour
{
    [Header("Patrol")]
    public float patrolSpeed = 1.5f;
    public float chaseSpeed = 3.5f;
    public float detectionRange = 6f;
    public bool stopAtLedge = true;

    [Header("Raycast")]
    public LayerMask groundLayer;
    public Transform ledgeCheckLeft;
    public Transform ledgeCheckRight;
    public float ledgeCheckDistance = 0.5f;

    private EnemyBase enemy;
    private Transform player;
    private bool isChasing;
    private int facingDir = -1;

    void Awake()
    {
        enemy = GetComponent<EnemyBase>();
        var pc = FindObjectOfType<PlayerController>();
        if (pc) player = pc.transform;
    }

    void Update()
    {
        if (enemy == null || PlayerController.Instance == null) return;

        float dist = player ? Vector2.Distance(transform.position, player.position) : float.MaxValue;
        isChasing = dist <= detectionRange && player != null;

        enemy.moveSpeed = isChasing ? chaseSpeed : patrolSpeed;

        if (isChasing)
        {
            // Đuổi theo người chơi
            facingDir = player.position.x > transform.position.x ? 1 : -1;
        }
        else
        {
            CheckLedgeAndWall();
        }

        // Đồng bộ hướng với EnemyBase
        // (EnemyBase.direction được set qua reflection hoặc public property)
    }

    void CheckLedgeAndWall()
    {
        // Kiểm tra mép sàn
        if (stopAtLedge)
        {
            Transform ledgeCheck = facingDir == 1 ? ledgeCheckRight : ledgeCheckLeft;
            if (ledgeCheck != null)
            {
                bool groundAhead = Physics2D.Raycast(
                    ledgeCheck.position, Vector2.down, ledgeCheckDistance, groundLayer);
                if (!groundAhead)
                {
                    facingDir *= -1; // Quay đầu tại mép
                    return;
                }
            }
        }

        // Kiểm tra tường
        RaycastHit2D wallHit = Physics2D.Raycast(
            transform.position, Vector2.right * facingDir, 0.6f, groundLayer);
        if (wallHit.collider != null)
            facingDir *= -1;
    }

    void OnDrawGizmosSelected()
    {
        // Hiển thị vùng phát hiện trong Editor
        Gizmos.color = Color.yellow;
        Gizmos.DrawWireSphere(transform.position, detectionRange);
        if (ledgeCheckLeft)
        {
            Gizmos.color = Color.red;
            Gizmos.DrawLine(ledgeCheckLeft.position,
                ledgeCheckLeft.position + Vector3.down * ledgeCheckDistance);
        }
        if (ledgeCheckRight)
        {
            Gizmos.color = Color.red;
            Gizmos.DrawLine(ledgeCheckRight.position,
                ledgeCheckRight.position + Vector3.down * ledgeCheckDistance);
        }
    }
}

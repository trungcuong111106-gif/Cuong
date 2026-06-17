using UnityEngine;

/// <summary>
/// Hiệu ứng Parallax nhiều lớp nền cuộn với tốc độ khác nhau.
/// Gắn vào từng layer nền (mây, núi, cây...).
/// </summary>
public class ParallaxBackground : MonoBehaviour
{
    [System.Serializable]
    public class ParallaxLayer
    {
        public Transform layerTransform;
        [Range(0f, 1f)]
        public float parallaxFactor; // 0 = tĩnh hoàn toàn, 1 = cuộn cùng camera
        public bool infiniteHorizontal = true;
        [HideInInspector] public float spriteWidth;
        [HideInInspector] public Vector3 startPos;
    }

    public ParallaxLayer[] layers;
    private Transform cam;

    void Start()
    {
        cam = Camera.main.transform;
        foreach (var layer in layers)
        {
            layer.startPos = layer.layerTransform.position;
            var sr = layer.layerTransform.GetComponent<SpriteRenderer>();
            layer.spriteWidth = sr ? sr.bounds.size.x : 10f;
        }
    }

    void LateUpdate()
    {
        foreach (var layer in layers)
        {
            float distX = cam.position.x * layer.parallaxFactor;
            float distY = cam.position.y * layer.parallaxFactor * 0.3f; // ít hơn ở trục Y

            layer.layerTransform.position = new Vector3(
                layer.startPos.x + distX,
                layer.startPos.y + distY,
                layer.layerTransform.position.z);

            // Lặp vô hạn theo chiều ngang
            if (layer.infiniteHorizontal)
            {
                float relX = cam.position.x * (1 - layer.parallaxFactor);
                if (relX > layer.startPos.x + layer.spriteWidth)
                    layer.startPos.x += layer.spriteWidth;
                else if (relX < layer.startPos.x - layer.spriteWidth)
                    layer.startPos.x -= layer.spriteWidth;
            }
        }
    }
}

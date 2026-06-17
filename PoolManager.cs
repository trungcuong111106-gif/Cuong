using UnityEngine;
using System.Collections.Generic;

/// <summary>
/// Object Pool – tái sử dụng GameObject để tránh GC spikes.
/// Dùng cho: coin, fireball, enemy debris, floating score text.
/// </summary>
public class PoolManager : MonoBehaviour
{
    public static PoolManager Instance { get; private set; }

    [System.Serializable]
    public class Pool
    {
        public string tag;
        public GameObject prefab;
        public int initialSize = 10;
    }

    public List<Pool> pools;
    private Dictionary<string, Queue<GameObject>> poolDict = new Dictionary<string, Queue<GameObject>>();

    void Awake()
    {
        Instance = this;
        foreach (var pool in pools)
        {
            var queue = new Queue<GameObject>();
            for (int i = 0; i < pool.initialSize; i++)
            {
                var obj = Instantiate(pool.prefab, transform);
                obj.SetActive(false);
                queue.Enqueue(obj);
            }
            poolDict[pool.tag] = queue;
        }
    }

    public GameObject Spawn(string tag, Vector3 position, Quaternion rotation)
    {
        if (!poolDict.ContainsKey(tag))
        {
            Debug.LogWarning($"[Pool] Tag '{tag}' không tồn tại.");
            return null;
        }

        var queue = poolDict[tag];
        GameObject obj;

        if (queue.Count > 0 && !queue.Peek().activeInHierarchy)
        {
            obj = queue.Dequeue();
        }
        else
        {
            // Mở rộng pool nếu hết
            var pool = pools.Find(p => p.tag == tag);
            obj = Instantiate(pool.prefab, transform);
        }

        obj.transform.SetPositionAndRotation(position, rotation);
        obj.SetActive(true);

        var poolable = obj.GetComponent<IPoolable>();
        poolable?.OnSpawn();

        queue.Enqueue(obj);
        return obj;
    }

    public void Despawn(string tag, GameObject obj, float delay = 0f)
    {
        if (delay > 0)
            StartCoroutine(DespawnRoutine(obj, delay));
        else
            DespawnImmediate(obj);
    }

    void DespawnImmediate(GameObject obj)
    {
        obj.GetComponent<IPoolable>()?.OnDespawn();
        obj.SetActive(false);
    }

    System.Collections.IEnumerator DespawnRoutine(GameObject obj, float delay)
    {
        yield return new WaitForSeconds(delay);
        DespawnImmediate(obj);
    }
}

/// <summary>Interface cho object có thể pooled.</summary>
public interface IPoolable
{
    void OnSpawn();
    void OnDespawn();
}

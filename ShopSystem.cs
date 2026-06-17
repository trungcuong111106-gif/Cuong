using UnityEngine;
using UnityEngine.UI;
using TMPro;
using System.Collections.Generic;

/// <summary>
/// Hệ thống cửa hàng: dùng xu mua trang phục và vật phẩm bổ trợ.
/// </summary>
public class ShopSystem : MonoBehaviour
{
    public static ShopSystem Instance { get; private set; }

    [System.Serializable]
    public class ShopItem
    {
        public string id;
        public string displayName;
        public int price;           // Giá tính bằng xu
        public ShopItemType type;
        public Sprite preview;
        public bool purchased;
        public bool equipped;
    }

    public enum ShopItemType { Skin, PowerupBoost, ExtraLife }

    [Header("Catalog")]
    public List<ShopItem> catalog = new List<ShopItem>
    {
        new ShopItem { id="skin_fire",    displayName="Bộ Đồ Lửa",       price=200, type=ShopItemType.Skin },
        new ShopItem { id="skin_ice",     displayName="Bộ Đồ Băng",       price=300, type=ShopItemType.Skin },
        new ShopItem { id="skin_gold",    displayName="Mario Vàng",        price=500, type=ShopItemType.Skin },
        new ShopItem { id="boost_speed",  displayName="Tăng Tốc x1.5",    price=150, type=ShopItemType.PowerupBoost },
        new ShopItem { id="boost_jump",   displayName="Nhảy Cao +20%",     price=150, type=ShopItemType.PowerupBoost },
        new ShopItem { id="extra_life",   displayName="Mạng Sống Thêm",    price=100, type=ShopItemType.ExtraLife },
    };

    [Header("UI")]
    public GameObject shopPanel;
    public Transform itemGrid;
    public GameObject shopItemUIPrefab;
    public TextMeshProUGUI shopCoinsDisplay;

    private string equippedSkinId;

    void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        LoadPurchases();
    }

    public void OpenShop()
    {
        shopPanel?.SetActive(true);
        shopCoinsDisplay?.SetText($"Xu: {GameManager.Instance?.coins ?? 0}");
        PopulateGrid();
    }

    public void CloseShop() => shopPanel?.SetActive(false);

    void PopulateGrid()
    {
        if (itemGrid == null) return;
        foreach (Transform child in itemGrid) Destroy(child.gameObject);
        foreach (var item in catalog)
        {
            var go = Instantiate(shopItemUIPrefab, itemGrid);
            go.GetComponent<ShopItemUI>()?.Setup(item, this);
        }
    }

    public bool TryBuy(ShopItem item)
    {
        var gm = GameManager.Instance;
        if (gm == null || gm.coins < item.price)
        {
            UIManager.Instance?.ShowMessage("Không đủ xu!", 1.5f);
            return false;
        }

        if (item.type == ShopItemType.ExtraLife)
        {
            gm.coins -= item.price;
            gm.GainExtraLife();
            UIManager.Instance?.UpdateCoins(gm.coins);
            UIManager.Instance?.ShowMessage("Đã mua thêm 1 mạng!", 2f);
            return true;
        }

        gm.coins -= item.price;
        UIManager.Instance?.UpdateCoins(gm.coins);
        item.purchased = true;
        SavePurchases();
        UIManager.Instance?.ShowMessage($"Đã mua: {item.displayName}", 2f);
        shopCoinsDisplay?.SetText($"Xu: {gm.coins}");
        return true;
    }

    public void Equip(ShopItem item)
    {
        if (!item.purchased) return;
        if (item.type != ShopItemType.Skin) return;

        // Bỏ trang bị skin cũ
        foreach (var s in catalog)
            if (s.type == ShopItemType.Skin) s.equipped = false;

        item.equipped = true;
        equippedSkinId = item.id;
        ApplySkin(item.id);
        SavePurchases();
        UIManager.Instance?.ShowMessage($"Đã trang bị: {item.displayName}", 1.5f);
    }

    void ApplySkin(string skinId)
    {
        // TODO: Thay sprite của PlayerController theo skinId
        Debug.Log($"[Shop] Applying skin: {skinId}");
    }

    public bool HasBoost(string boostId)
    {
        var item = catalog.Find(i => i.id == boostId);
        return item != null && item.purchased;
    }

    void SavePurchases()
    {
        foreach (var item in catalog)
        {
            PlayerPrefs.SetInt("shop_" + item.id, item.purchased ? 1 : 0);
            PlayerPrefs.SetInt("equip_" + item.id, item.equipped ? 1 : 0);
        }
        PlayerPrefs.Save();
    }

    void LoadPurchases()
    {
        foreach (var item in catalog)
        {
            item.purchased = PlayerPrefs.GetInt("shop_" + item.id, 0) == 1;
            item.equipped = PlayerPrefs.GetInt("equip_" + item.id, 0) == 1;
            if (item.equipped) equippedSkinId = item.id;
        }
    }
}

// ─── Shop Item UI Component ────────────────────────────────────────────────────

public class ShopItemUI : MonoBehaviour
{
    public Image previewImage;
    public TextMeshProUGUI nameText;
    public TextMeshProUGUI priceText;
    public Button buyButton;
    public Button equipButton;
    public GameObject purchasedBadge;

    private ShopItem item;
    private ShopSystem shop;

    public void Setup(ShopSystem.ShopItem shopItem, ShopSystem shopSystem)
    {
        item = shopItem;
        shop = shopSystem;

        nameText?.SetText(item.displayName);
        priceText?.SetText($"{item.price} xu");
        previewImage?.sprite != null ? previewImage.sprite = item.preview : null;
        purchasedBadge?.SetActive(item.purchased);

        buyButton?.gameObject.SetActive(!item.purchased);
        equipButton?.gameObject.SetActive(item.purchased && item.type == ShopSystem.ShopItemType.Skin);

        buyButton?.onClick.AddListener(() =>
        {
            if (shop.TryBuy(item)) Setup(item, shop);
        });
        equipButton?.onClick.AddListener(() => shop.Equip(item));
    }
}

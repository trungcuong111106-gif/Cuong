using UnityEngine;
using System.Collections.Generic;

/// <summary>
/// Quản lý toàn bộ âm thanh: nhạc nền và SFX.
/// </summary>
public class AudioManager : MonoBehaviour
{
    public static AudioManager Instance { get; private set; }

    [System.Serializable]
    public class SoundEntry
    {
        public string name;
        public AudioClip clip;
        [Range(0f, 1f)] public float volume = 1f;
        [Range(0.8f, 1.2f)] public float pitch = 1f;
    }

    [Header("Music")]
    public AudioSource musicSource;
    public List<SoundEntry> musicTracks;

    [Header("SFX")]
    public AudioSource sfxSource;
    public List<SoundEntry> sfxSounds;

    private Dictionary<string, SoundEntry> musicDict = new Dictionary<string, SoundEntry>();
    private Dictionary<string, SoundEntry> sfxDict = new Dictionary<string, SoundEntry>();

    void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        DontDestroyOnLoad(gameObject);

        foreach (var m in musicTracks) musicDict[m.name] = m;
        foreach (var s in sfxSounds) sfxDict[s.name] = s;
    }

    public void PlayMusic(string name)
    {
        if (!musicDict.TryGetValue(name, out var entry)) return;
        if (musicSource.clip == entry.clip && musicSource.isPlaying) return;
        musicSource.clip = entry.clip;
        musicSource.volume = entry.volume;
        musicSource.loop = true;
        musicSource.Play();
    }

    public void PlaySFX(string name)
    {
        if (!sfxDict.TryGetValue(name, out var entry)) return;
        sfxSource.pitch = entry.pitch;
        sfxSource.PlayOneShot(entry.clip, entry.volume);
    }

    public void SetMasterVolume(float vol) => AudioListener.volume = vol;
    public void StopMusic() => musicSource.Stop();
}

/**
 * fps-data.js — Benchmark data for FPS Estimator
 * Contains hardcoded FPS estimates for specific GPU/Resolution combinations
 */
const FPS_DATA = {
    games: [
        { id: 'cyberpunk', name: 'Cyberpunk 2077', icon: 'fa-robot', demand: 1.12 },
        { id: 'rdr2', name: 'Red Dead Redemption 2', icon: 'fa-horse', demand: 1.02 },
        { id: 'warzone', name: 'Warzone', icon: 'fa-person-rifle', demand: 0.98 },
        { id: 'wukong', name: 'Black Myth: Wukong', icon: 'fa-dragon', demand: 1.14 },
        { id: 'bg3', name: "Baldur's Gate 3", icon: 'fa-dice-d20', demand: 0.86 },
        { id: 'starfield', name: 'Starfield', icon: 'fa-shuttle-space', demand: 1.08 },
        { id: 'valorant', name: 'Valorant', icon: 'fa-crosshairs', demand: 0.62 },
        { id: 'forza5', name: 'Forza Horizon 5', icon: 'fa-flag-checkered', demand: 0.92 },
        { id: 'fortnite', name: 'Fortnite', icon: 'fa-bolt', demand: 0.78 },
        { id: 'gta5', name: 'GTA V', icon: 'fa-car', demand: 0.74 },
        { id: 'helldivers2', name: 'Helldivers 2', icon: 'fa-skull', demand: 0.94 },
        { id: 'eldenring', name: 'Elden Ring', icon: 'fa-ring', demand: 0.88 }
    ],
    // Benchmark mapping: GPU_ID -> { Game_ID -> { Resolution -> FPS } }
    benchmarks: {
        // NVIDIA RTX 4090
        "1": {
            "cyberpunk": { "1080p": 165, "1440p": 128, "4K": 78 },
            "rdr2":      { "1080p": 185, "1440p": 145, "4K": 98 },
            "warzone":   { "1080p": 214, "1440p": 172, "4K": 112 },
            "fortnite":  { "1080p": 285, "1440p": 232, "4K": 154 },
            "valorant":  { "1080p": 420, "1440p": 360, "4K": 248 },
            "gta5":      { "1080p": 195, "1440p": 185, "4K": 142 }
        },
        // AMD Radeon RX 7900 XTX
        "2": {
            "cyberpunk": { "1080p": 142, "1440p": 105, "4K": 62 },
            "rdr2":      { "1080p": 168, "1440p": 132, "4K": 88 },
            "warzone":   { "1080p": 196, "1440p": 154, "4K": 96 },
            "fortnite":  { "1080p": 266, "1440p": 218, "4K": 136 },
            "valorant":  { "1080p": 390, "1440p": 336, "4K": 226 },
            "gta5":      { "1080p": 190, "1440p": 178, "4K": 125 }
        },
        // NVIDIA RTX 4070 Ti Super
        "3": {
            "cyberpunk": { "1080p": 115, "1440p": 85,  "4K": 48 },
            "rdr2":      { "1080p": 135, "1440p": 102, "4K": 65 },
            "warzone":   { "1080p": 158, "1440p": 122, "4K": 72 },
            "fortnite":  { "1080p": 228, "1440p": 176, "4K": 104 },
            "valorant":  { "1080p": 340, "1440p": 288, "4K": 190 },
            "gta5":      { "1080p": 185, "1440p": 165, "4K": 95 }
        },
        // NVIDIA RTX 4080 Super
        "17": {
            "cyberpunk": { "1080p": 145, "1440p": 108, "4K": 66 },
            "rdr2":      { "1080p": 168, "1440p": 128, "4K": 84 },
            "warzone":   { "1080p": 188, "1440p": 146, "4K": 90 },
            "fortnite":  { "1080p": 262, "1440p": 210, "4K": 128 },
            "valorant":  { "1080p": 388, "1440p": 330, "4K": 220 },
            "gta5":      { "1080p": 192, "1440p": 176, "4K": 118 }
        },
        // AMD Radeon RX 7800 XT
        "18": {
            "cyberpunk": { "1080p": 95, "1440p": 68, "4K": 38 },
            "rdr2":      { "1080p": 118, "1440p": 86, "4K": 54 },
            "warzone":   { "1080p": 138, "1440p": 102, "4K": 60 },
            "fortnite":  { "1080p": 204, "1440p": 152, "4K": 88 },
            "valorant":  { "1080p": 306, "1440p": 252, "4K": 164 },
            "gta5":      { "1080p": 170, "1440p": 146, "4K": 82 }
        }
    },
    // Bottleneck Multipliers (CPU based)
    // CPU_ID -> Multiplier (how much performance is retained)
    cpuTiers: {
        "4": 1.0,  // i9-14900K (High end)
        "5": 1.0,  // R9 7950X (High end)
        "6": 0.92, // R5 7700X (Mid-High end)
        // Default for unknown or lower end CPUs
        "default": 0.85
    }
};

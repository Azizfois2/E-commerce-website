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
        { id: 'eldenring', name: 'Elden Ring', icon: 'fa-ring', demand: 0.88 },
        { id: 'fc25', name: 'EA SPORTS FC 25', icon: 'fa-futbol', demand: 0.70 },
        { id: 'minecraft', name: 'Minecraft', icon: 'fa-cube', demand: 0.40 },
        { id: 'rocketleague', name: 'Rocket League', icon: 'fa-car-burst', demand: 0.50 },
        { id: 'pragmata', name: 'Pragmata', icon: 'fa-rocket', demand: 1.10 },
        { id: 're4', name: 'Resident Evil 4', icon: 'fa-skull-crossbones', demand: 0.82 },
        { id: 'persona5', name: 'Persona 5 Royal', icon: 'fa-masks-theater', demand: 0.45 },
        { id: 'efootball', name: 'eFootball 2025', icon: 'fa-futbol', demand: 0.68 }
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
            "gta5":      { "1080p": 195, "1440p": 185, "4K": 142 },
            "fc25":      { "1080p": 260, "1440p": 220, "4K": 155 },
            "minecraft": { "1080p": 450, "1440p": 400, "4K": 320 },
            "rocketleague": { "1080p": 350, "1440p": 290, "4K": 200 },
            "pragmata":  { "1080p": 158, "1440p": 120, "4K": 72 },
            "re4":       { "1080p": 210, "1440p": 168, "4K": 105 },
            "persona5":  { "1080p": 420, "1440p": 380, "4K": 300 },
            "efootball": { "1080p": 280, "1440p": 240, "4K": 170 }
        },
        // AMD Radeon RX 7900 XTX
        "2": {
            "cyberpunk": { "1080p": 142, "1440p": 105, "4K": 62 },
            "rdr2":      { "1080p": 168, "1440p": 132, "4K": 88 },
            "warzone":   { "1080p": 196, "1440p": 154, "4K": 96 },
            "fortnite":  { "1080p": 266, "1440p": 218, "4K": 136 },
            "valorant":  { "1080p": 390, "1440p": 336, "4K": 226 },
            "gta5":      { "1080p": 190, "1440p": 178, "4K": 125 },
            "fc25":      { "1080p": 240, "1440p": 200, "4K": 140 },
            "minecraft": { "1080p": 420, "1440p": 380, "4K": 300 },
            "rocketleague": { "1080p": 320, "1440p": 265, "4K": 180 },
            "pragmata":  { "1080p": 134, "1440p": 98, "4K": 58 },
            "re4":       { "1080p": 192, "1440p": 148, "4K": 92 },
            "persona5":  { "1080p": 400, "1440p": 360, "4K": 280 },
            "efootball": { "1080p": 260, "1440p": 220, "4K": 155 }
        },
        // NVIDIA RTX 4070 Ti Super
        "3": {
            "cyberpunk": { "1080p": 115, "1440p": 85,  "4K": 48 },
            "rdr2":      { "1080p": 135, "1440p": 102, "4K": 65 },
            "warzone":   { "1080p": 158, "1440p": 122, "4K": 72 },
            "fortnite":  { "1080p": 228, "1440p": 176, "4K": 104 },
            "valorant":  { "1080p": 340, "1440p": 288, "4K": 190 },
            "gta5":      { "1080p": 185, "1440p": 165, "4K": 95 },
            "fc25":      { "1080p": 200, "1440p": 168, "4K": 118 },
            "minecraft": { "1080p": 360, "1440p": 320, "4K": 250 },
            "rocketleague": { "1080p": 270, "1440p": 220, "4K": 150 },
            "pragmata":  { "1080p": 108, "1440p": 78, "4K": 44 },
            "re4":       { "1080p": 155, "1440p": 118, "4K": 72 },
            "persona5":  { "1080p": 350, "1440p": 310, "4K": 240 },
            "efootball": { "1080p": 215, "1440p": 180, "4K": 125 }
        },
        // NVIDIA RTX 4080 Super
        "17": {
            "cyberpunk": { "1080p": 145, "1440p": 108, "4K": 66 },
            "rdr2":      { "1080p": 168, "1440p": 128, "4K": 84 },
            "warzone":   { "1080p": 188, "1440p": 146, "4K": 90 },
            "fortnite":  { "1080p": 262, "1440p": 210, "4K": 128 },
            "valorant":  { "1080p": 388, "1440p": 330, "4K": 220 },
            "gta5":      { "1080p": 192, "1440p": 176, "4K": 118 },
            "fc25":      { "1080p": 248, "1440p": 210, "4K": 148 },
            "minecraft": { "1080p": 440, "1440p": 390, "4K": 310 },
            "rocketleague": { "1080p": 335, "1440p": 278, "4K": 192 },
            "pragmata":  { "1080p": 138, "1440p": 100, "4K": 60 },
            "re4":       { "1080p": 198, "1440p": 152, "4K": 96 },
            "persona5":  { "1080p": 410, "1440p": 370, "4K": 290 },
            "efootball": { "1080p": 268, "1440p": 228, "4K": 162 }
        },
        // AMD Radeon RX 7800 XT
        "18": {
            "cyberpunk": { "1080p": 95, "1440p": 68, "4K": 38 },
            "rdr2":      { "1080p": 118, "1440p": 86, "4K": 54 },
            "warzone":   { "1080p": 138, "1440p": 102, "4K": 60 },
            "fortnite":  { "1080p": 204, "1440p": 152, "4K": 88 },
            "valorant":  { "1080p": 306, "1440p": 252, "4K": 164 },
            "gta5":      { "1080p": 170, "1440p": 146, "4K": 82 },
            "fc25":      { "1080p": 180, "1440p": 150, "4K": 105 },
            "minecraft": { "1080p": 330, "1440p": 290, "4K": 220 },
            "rocketleague": { "1080p": 245, "1440p": 200, "4K": 135 },
            "pragmata":  { "1080p": 88, "1440p": 62, "4K": 34 },
            "re4":       { "1080p": 140, "1440p": 105, "4K": 64 },
            "persona5":  { "1080p": 320, "1440p": 280, "4K": 210 },
            "efootball": { "1080p": 195, "1440p": 165, "4K": 115 }
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

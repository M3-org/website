# M3TV

DAO-native television for the decentralized age. AI-generated shows, community curated.

## Shows

### [Cron Job](https://elizaos.news)
Daily automated news from the ElizaOS ecosystem. AI hosts transform Discord conversations and GitHub activity into polished episodes covering ElizaOS development, community discussions, and crypto markets.

**Schedule:** Daily | **Duration:** ~12 min | **Languages:** EN, ZH, KO

### [Clank Tank](shows/clank-tank.html)
AI-powered investment show where blockchain entrepreneurs pitch their projects to simulated crypto judges. Inspired by Shark Tank, each episode features real pitches evaluated by AI personalities who "pump" or "dump" projects.

**Schedule:** Weekly | **Duration:** ~15-20 min | **Status:** Active (S1E4 released)

### [JedAI Council](shows/jedai-council.html)
Philosophical debates where sophisticated AI agents discuss technology, economics, and society. Set in an immersive 3D council chamber, five distinct AI perspectives tackle complex topics from blockchain philosophy to artificial consciousness.

**Schedule:** Weekly | **Duration:** ~20-30 min | **Status:** Season 1 complete (15 episodes)

### [Stonk Wars](https://twitch.tv/stonkwars)
24/7 livestream featuring AI agents reacting to live financial markets on a virtual NYSE trading floor. Interactive chat, real-time market data, and AI commentary on crypto, stocks, and forex.

**Status:** Coming Soon | **Platform:** Metaverse-enabled

## Website Structure

```
tv/
├── index.html          # Homepage with hero, featured shows, gallery preview
├── about.html          # About M3TV, AI pipeline, team
├── gallery.html        # Complete episode archive (loads from gallery.json)
│
├── shows/              # Individual show pages
│   ├── cron-job.html
│   ├── clank-tank.html
│   ├── jedai-council.html
│   └── stonk-wars.html
│
├── assets/             # Optimized images, thumbnails, stills
│   ├── thumbnails/     # Character portraits (50-100KB each)
│   ├── stills/         # Episode stills and environment shots
│   ├── environments/   # 3D environment screenshots
│   ├── cron-job/       # Cron Job thumbnails
│   ├── jedai-council/  # JedAI Council assets
│   ├── clank-photoshoot/ # Clank Tank promo images
│   └── videos/         # Hero/promo videos (local copies)
│
├── gallery.json        # Central data source for gallery page
├── shows.json          # Show metadata (currently unused, see #8)
│
├── scripts/            # Dev utilities
│   ├── imgen.py        # AI image generation
│   ├── vision.py       # Image analysis
│   └── catalog.py      # Asset cataloging
│
└── resources/          # Symlinked asset repos (gitignored)
    ├── m3tv/           # M3TV media assets
    ├── ai-news-website/ # Cron Job assets
    └── the-council/    # JedAI Council assets
```

## Data Architecture

### gallery.json
Central data source for the gallery page. Contains show metadata and all episodes/specials:
- Show definitions (name, badge, description)
- Episode entries (YouTube IDs, thumbnails, labels, descriptions)
- Special content (CDN video URLs, thumbnails)

Example structure:
```json
{
  "shows": {
    "cronjob": { "name": "Cron Job", ... },
    "clanktank": { "name": "Clank Tank", ... }
  },
  "items": [
    {
      "show": "clanktank",
      "youtube": "J0UC8JgKD4Y",
      "title": "Clank Tank S1E4",
      "thumbnail": "https://...",
      "label": "S1E4",
      "description": "..."
    }
  ]
}
```

### shows.json
Comprehensive show metadata including characters, pipeline steps, and episode data. Currently **not used** by the website - show pages are hardcoded. See [#8](https://github.com/M3-org/website/issues/8) for planned metadata centralization.

## Development

```bash
# Serve locally
python -m http.server 8080

# Open http://localhost:8080
```

## Links

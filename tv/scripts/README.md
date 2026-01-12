# M3TV Scripts

Development utilities for M3TV content creation.

## Scripts

### `imgen.py`
Image generation using AI models via OpenRouter API.

```bash
python scripts/imgen.py "prompt describing the image" -o output.png --aspect 16:9
```

Requires `OPENROUTER_API_KEY` in `.env`

### `vision.py`
Analyze images/screenshots using vision models for design feedback.

```bash
python scripts/vision.py screenshot.png "Is this design premium? What improvements would help?"
```

### `catalog.py`
Generate descriptions for image assets using vision models.

```bash
python scripts/catalog.py resources/m3tv/folder/ -o assets/descriptions.json
```

## Setup

```bash
# Create .env with your API key
echo "OPENROUTER_API_KEY=your-key-here" > .env

# Install dependencies
pip install requests python-dotenv
```

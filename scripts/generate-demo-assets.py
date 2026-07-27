#!/usr/bin/env python3
"""Generate original, trademark-free demo graphics for Sports Avenue.

The generated images are deliberately simple portfolio mockups. They do not
copy official league badges, club crests, kit patterns, sponsor marks, or
commercial product photography.
"""

from __future__ import annotations

from pathlib import Path
from typing import Iterable

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
IMAGE_ROOT = ROOT / "public" / "assets" / "images"
LEAGUE_ROOT = IMAGE_ROOT / "leagues"
JERSEY_ROOT = IMAGE_ROOT / "jerseys"
CHECKOUT_ROOT = IMAGE_ROOT / "checkout"

LEAGUES = [
    ("northstar-league.png", "NORTHSTAR", "LEAGUE", "#16324F", "#F2C14E"),
    ("suncoast-championship.png", "SUNCOAST", "CHAMPIONSHIP", "#8C2F39", "#F4D35E"),
    ("heritage-division.png", "HERITAGE", "DIVISION", "#2D6A4F", "#D8F3DC"),
    ("alpine-elite-league.png", "ALPINE ELITE", "LEAGUE", "#3D405B", "#E07A5F"),
    ("metropolitan-ligue.png", "METROPOLITAN", "LIGUE", "#5A189A", "#E0AAFF"),
]

JERSEYS = [
    ("northbridge-falcons-home.png", "NF", "HOME", "#17324D", "#F2C14E", "stripes"),
    ("northbridge-falcons-away.png", "NF", "AWAY", "#F4F1DE", "#17324D", "sash"),
    ("harbor-city-home.png", "HC", "HOME", "#0077B6", "#90E0EF", "hoops"),
    ("harbor-city-away.png", "HC", "AWAY", "#023047", "#FB8500", "split"),
    ("solara-athletic-home.png", "SA", "HOME", "#F77F00", "#FCBF49", "sunburst"),
    ("solara-athletic-away.png", "SA", "AWAY", "#F8F9FA", "#D62828", "sash"),
    ("costa-verde-home.png", "CV", "HOME", "#2A9D8F", "#E9C46A", "hoops"),
    ("costa-verde-away.png", "CV", "AWAY", "#264653", "#E76F51", "split"),
    ("aurora-calcio-home.png", "AC", "HOME", "#6A4C93", "#8AC926", "stripes"),
    ("aurora-calcio-away.png", "AC", "AWAY", "#FFCA3A", "#6A4C93", "sash"),
    ("vesuvio-united-home.png", "VU", "HOME", "#A4161A", "#F5F3F4", "split"),
    ("vesuvio-united-away.png", "VU", "AWAY", "#161A1D", "#E5383B", "hoops"),
    ("rhine-wolves-home.png", "RW", "HOME", "#1D3557", "#A8DADC", "chevron"),
    ("rhine-wolves-away.png", "RW", "AWAY", "#E63946", "#F1FAEE", "stripes"),
    ("alpine-04-home.png", "A04", "HOME", "#386641", "#A7C957", "sash"),
    ("alpine-04-away.png", "A04", "AWAY", "#F2E8CF", "#6A994E", "chevron"),
    ("capital-comets-home.png", "CC", "HOME", "#4A4E69", "#F2E9E4", "sunburst"),
    ("capital-comets-away.png", "CC", "AWAY", "#C9ADA7", "#22223B", "split"),
    ("azure-olympique-home.png", "AO", "HOME", "#00509D", "#FFD500", "hoops"),
    ("azure-olympique-away.png", "AO", "AWAY", "#FFFFFF", "#00509D", "chevron"),
]


def font(size: int) -> ImageFont.ImageFont:
    return ImageFont.load_default(size=size)


def centered_text(draw: ImageDraw.ImageDraw, xy: tuple[int, int], text: str, *, size: int, fill: str) -> None:
    draw.text(xy, text, font=font(size), fill=fill, anchor="mm", align="center")


def ensure_directories() -> None:
    for directory in (LEAGUE_ROOT, JERSEY_ROOT, CHECKOUT_ROOT):
        directory.mkdir(parents=True, exist_ok=True)


def clear_directory(directory: Path, suffixes: Iterable[str]) -> None:
    for path in directory.iterdir():
        if path.is_file() and path.suffix.lower() in suffixes:
            path.unlink()


def make_league_badge(filename: str, title: str, subtitle: str, primary: str, accent: str) -> None:
    image = Image.new("RGB", (512, 512), "#F7F7F7")
    draw = ImageDraw.Draw(image)
    draw.rounded_rectangle((36, 36, 476, 476), radius=72, fill=primary, outline=accent, width=18)
    draw.ellipse((144, 104, 368, 328), fill="#FFFFFF", outline=accent, width=12)
    draw.regular_polygon((256, 216, 78), n_sides=5, rotation=-90, fill=accent)
    centered_text(draw, (256, 382), title, size=34, fill="#FFFFFF")
    centered_text(draw, (256, 427), subtitle, size=24, fill=accent)
    image.save(LEAGUE_ROOT / filename, optimize=True)


def jersey_polygon() -> list[tuple[int, int]]:
    return [(244, 170), (324, 122), (400, 162), (476, 122), (556, 170), (680, 270), (598, 366), (548, 322), (548, 692), (252, 692), (252, 322), (202, 366), (120, 270)]


def apply_pattern(draw: ImageDraw.ImageDraw, pattern: str, primary: str, accent: str) -> None:
    if pattern == "stripes":
        for x in range(230, 575, 70):
            draw.rectangle((x, 160, x + 34, 700), fill=accent)
    elif pattern == "hoops":
        for y in range(250, 620, 80):
            draw.rectangle((160, y, 640, y + 34), fill=accent)
    elif pattern == "sash":
        draw.polygon([(170, 205), (235, 145), (630, 615), (575, 690)], fill=accent)
    elif pattern == "split":
        draw.rectangle((400, 130, 690, 700), fill=accent)
    elif pattern == "chevron":
        draw.line([(230, 275), (400, 430), (570, 275)], fill=accent, width=46, joint="curve")
    elif pattern == "sunburst":
        center = (400, 410)
        points = [(400, 150), (438, 315), (575, 210), (490, 350), (650, 340), (490, 395), (620, 535), (460, 450), (500, 665), (400, 490), (300, 665), (340, 450), (180, 535), (310, 395), (150, 340), (310, 350), (225, 210), (362, 315)]
        draw.polygon(points, fill=accent)
        draw.ellipse((340, 350, 460, 470), fill=primary)


def make_jersey(filename: str, initials: str, variant: str, primary: str, accent: str, pattern: str) -> None:
    image = Image.new("RGB", (800, 800), "#EEF2F5")
    draw = ImageDraw.Draw(image)
    draw.rounded_rectangle((36, 36, 764, 764), radius=42, fill="#FFFFFF", outline="#D7DEE5", width=5)

    mask = Image.new("L", image.size, 0)
    mask_draw = ImageDraw.Draw(mask)
    mask_draw.polygon(jersey_polygon(), fill=255)
    layer = Image.new("RGB", image.size, primary)
    pattern_draw = ImageDraw.Draw(layer)
    apply_pattern(pattern_draw, pattern, primary, accent)
    image.paste(layer, mask=mask)
    draw = ImageDraw.Draw(image)
    draw.polygon(jersey_polygon(), outline="#17202A", width=8)
    draw.arc((330, 108, 470, 220), start=0, end=180, fill="#17202A", width=10)
    draw.line((324, 126, 362, 202), fill="#17202A", width=8)
    draw.line((476, 126, 438, 202), fill="#17202A", width=8)
    draw.rounded_rectangle((337, 315, 463, 430), radius=18, fill="#FFFFFF", outline="#17202A", width=5)
    centered_text(draw, (400, 372), initials, size=48 if len(initials) < 3 else 40, fill="#17202A")
    centered_text(draw, (400, 725), f"{variant} DEMO KIT", size=25, fill="#334155")
    image.save(JERSEY_ROOT / filename, optimize=True)


def make_placeholder_assets() -> None:
    no_image = Image.new("RGB", (800, 800), "#E5E7EB")
    draw = ImageDraw.Draw(no_image)
    draw.rounded_rectangle((96, 96, 704, 704), radius=48, fill="#F8FAFC", outline="#94A3B8", width=10)
    draw.polygon([(190, 590), (350, 380), (455, 500), (540, 410), (650, 590)], fill="#CBD5E1")
    draw.ellipse((205, 205, 315, 315), fill="#94A3B8")
    centered_text(draw, (400, 665), "IMAGE NOT AVAILABLE", size=30, fill="#475569")
    no_image.save(IMAGE_ROOT / "no-image.png", optimize=True)

    avatar = Image.new("RGB", (400, 480), "#E2E8F0")
    draw = ImageDraw.Draw(avatar)
    draw.ellipse((120, 75, 280, 235), fill="#64748B")
    draw.rounded_rectangle((74, 250, 326, 440), radius=110, fill="#64748B")
    centered_text(draw, (200, 452), "DEMO PROFILE", size=20, fill="#334155")
    avatar.save(IMAGE_ROOT / "no-user.jpg", quality=92, optimize=True)

    failed = Image.new("RGBA", (256, 256), "#00000000")
    draw = ImageDraw.Draw(failed)
    draw.ellipse((22, 22, 234, 234), fill="#FEE2E2", outline="#B91C1C", width=12)
    draw.line((82, 82, 174, 174), fill="#B91C1C", width=22)
    draw.line((174, 82, 82, 174), fill="#B91C1C", width=22)
    failed.save(CHECKOUT_ROOT / "checkout-failed.png", optimize=True)

    pending = Image.new("RGBA", (256, 256), "#00000000")
    draw = ImageDraw.Draw(pending)
    draw.ellipse((22, 22, 234, 234), fill="#FEF3C7", outline="#92400E", width=12)
    draw.line((128, 64, 128, 132), fill="#92400E", width=18)
    draw.line((128, 132, 174, 160), fill="#92400E", width=18)
    pending.save(CHECKOUT_ROOT / "checkout-pending.png", optimize=True)

    mailbox = Image.new("RGB", (512, 512), "#EFF6FF")
    draw = ImageDraw.Draw(mailbox)
    draw.rounded_rectangle((70, 170, 442, 390), radius=24, fill="#2563EB", outline="#1E3A8A", width=10)
    draw.polygon([(70, 190), (256, 315), (442, 190)], fill="#DBEAFE", outline="#1E3A8A")
    draw.rectangle((242, 82, 270, 195), fill="#1E3A8A")
    draw.polygon([(270, 82), (390, 120), (270, 158)], fill="#F97316")
    centered_text(draw, (256, 445), "ORDER UPDATE SENT", size=28, fill="#1E3A8A")
    mailbox.save(CHECKOUT_ROOT / "mailbox.jpg", quality=92, optimize=True)

    favicon = Image.new("RGBA", (64, 64), "#00000000")
    draw = ImageDraw.Draw(favicon)
    draw.rounded_rectangle((4, 4, 60, 60), radius=14, fill="#16324F")
    centered_text(draw, (32, 33), "SA", size=26, fill="#F2C14E")
    favicon.save(ROOT / "public" / "favicon.ico", sizes=[(16, 16), (32, 32), (48, 48), (64, 64)])


def main() -> None:
    ensure_directories()
    clear_directory(LEAGUE_ROOT, {".png", ".jpg", ".jpeg", ".webp"})
    clear_directory(JERSEY_ROOT, {".png", ".jpg", ".jpeg", ".webp"})

    for league in LEAGUES:
        make_league_badge(*league)

    for jersey in JERSEYS:
        make_jersey(*jersey)

    make_placeholder_assets()
    print(f"Generated {len(LEAGUES)} league badges and {len(JERSEYS)} jersey mockups.")


if __name__ == "__main__":
    main()

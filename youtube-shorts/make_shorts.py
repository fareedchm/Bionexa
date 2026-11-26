#!/usr/bin/env python3
"""Render 30-second vertical YouTube Shorts (1080x1920) for Bionexa formulations.

Usage:  python3 make_shorts.py            # renders every short in SHORTS
        python3 make_shorts.py dishwash   # renders one by key

Edit the SHORTS list below to change text or add new videos. Needs Pillow + ffmpeg.
"""
import math
import os
import subprocess
import sys

from PIL import Image, ImageDraw, ImageFont

W, H, FPS, DUR = 1080, 1920, 30, 30.0
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "videos")
WEBSITE = "bionexa.com"  # change to your real domain

# Brand colours (from the theme's theme.json)
PRIMARY = (15, 107, 95)
PRIMARY_DARK = (10, 74, 66)
ACCENT = (245, 165, 36)
TINT = (227, 242, 238)
WHITE = (255, 255, 255)
INK = (14, 31, 27)
MUTED = (75, 96, 90)

FONT_DIR = "/usr/share/fonts/opentype/inter/"


def font(weight, size):
    return ImageFont.truetype(FONT_DIR + f"Inter-{weight}.otf", size)


F_KICK = font("SemiBold", 40)
F_HOOK = font("ExtraBold", 92)
F_H2 = font("Bold", 64)
F_ITEM = font("Bold", 50)
F_SUB = font("Medium", 38)
F_NUM = font("ExtraBold", 54)
F_CTA = font("ExtraBold", 76)

SHORTS = [
    {
        "key": "hand-sanitizer",
        "kicker": "COSMETICS FORMULATION",
        "hook": "How hand sanitizer gel is made",
        "hook_sub": "5 ingredients. 4 steps.",
        "ingredients": [
            ("Ethanol", "Active – kills germs (WHO level)"),
            ("Carbomer", "Turns liquid into clear gel"),
            ("Glycerin", "Stops hands drying out"),
            ("Triethanolamine", "Neutralises & thickens"),
            ("Purified water", "Base of the formula"),
        ],
        "steps": [
            "Disperse carbomer in water – no lumps",
            "Add ethanol slowly while mixing",
            "Add glycerin for skin feel",
            "Neutralise – watch it turn to gel",
        ],
        "tip_title": "QC check",
        "tip": "Test alcohol content, pH and viscosity on every batch.",
    },
    {
        "key": "dishwash",
        "kicker": "HOUSEHOLD FORMULATION",
        "hook": "Make dishwash liquid like a factory",
        "hook_sub": "Scalable from 10 L to 10 tonnes",
        "ingredients": [
            ("LABSA", "Main grease-cutting surfactant"),
            ("Caustic soda", "Neutralises the LABSA"),
            ("SLES", "Rich, stable foam"),
            ("Salt", "Thickens the liquid"),
            ("Preservative", "Shelf life & safety"),
        ],
        "steps": [
            "Neutralise LABSA in water to neutral pH",
            "Blend in SLES gently – avoid air",
            "Add preservative, colour, fragrance",
            "Thicken with salt, little by little",
        ],
        "tip_title": "Pro tip",
        "tip": "Too much salt makes it THIN again. Add in small steps.",
    },
    {
        "key": "anti-dandruff-shampoo",
        "kicker": "PERSONAL CARE FORMULATION",
        "hook": "What's inside anti-dandruff shampoo?",
        "hook_sub": "The role of every ingredient",
        "ingredients": [
            ("SLES", "Primary cleanser & foam"),
            ("Cocamidopropyl betaine", "Mildness & foam boost"),
            ("Piroctone olamine", "The anti-dandruff active"),
            ("Polyquaternium-10", "Conditioning, soft hair"),
            ("Glycol distearate", "Pearly, premium look"),
        ],
        "steps": [
            "Hydrate the conditioning polymer",
            "Add surfactants, mix slowly",
            "Add active & pearlising agent",
            "Adjust pH and viscosity",
        ],
        "tip_title": "QC check",
        "tip": "Run stability tests before you launch in any market.",
    },
    {
        "key": "industrial-degreaser",
        "kicker": "INDUSTRIAL FORMULATION",
        "hook": "Heavy-duty degreaser – no solvents",
        "hook_sub": "Water-based. Workshop-strong.",
        "ingredients": [
            ("Alkaline builder", "Breaks down oil & grease"),
            ("Non-ionic surfactant", "Lifts & emulsifies dirt"),
            ("Hydrotrope", "Keeps the blend clear"),
            ("Chelating agent", "Works in hard water"),
            ("Water", "Safe, solvent-free base"),
        ],
        "steps": [
            "Dissolve the chelating agent in water",
            "Add the alkaline builder carefully",
            "Blend in surfactant & hydrotrope",
            "Check pH, clarity & dilution ratio",
        ],
        "tip_title": "Safety tip",
        "tip": "Always test on aluminium & painted surfaces first.",
    },
]

# ---------------------------------------------------------------- helpers


def clamp(x, a=0.0, b=1.0):
    return max(a, min(b, x))


def ease(x):
    x = clamp(x)
    return 1 - (1 - x) ** 3


def prog(t, start, dur=0.5):
    return ease((t - start) / dur)


def wrap(draw, text, fnt, max_w):
    words, lines, cur = text.split(), [], ""
    for w in words:
        test = (cur + " " + w).strip()
        if draw.textlength(test, font=fnt) <= max_w:
            cur = test
        else:
            lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


def text_block(img, xy, text, fnt, fill, alpha=1.0, max_w=900, gap=1.18, center=False):
    """Draw wrapped text with opacity onto img (RGBA). Returns bottom y."""
    if alpha <= 0:
        d = ImageDraw.Draw(img)
        return xy[1] + len(wrap(d, text, fnt, max_w)) * fnt.size * gap
    layer = Image.new("RGBA", img.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    x, y = xy
    for line in wrap(d, text, fnt, max_w):
        lx = (W - d.textlength(line, font=fnt)) / 2 if center else x
        d.text((lx, y), line, font=fnt, fill=fill + (int(255 * alpha),))
        y += fnt.size * gap
    img.alpha_composite(layer)
    return y


def rrect(img, box, r, fill, alpha=1.0):
    layer = Image.new("RGBA", img.size, (0, 0, 0, 0))
    ImageDraw.Draw(layer).rounded_rectangle(box, r, fill=fill + (int(255 * alpha),))
    img.alpha_composite(layer)


def background(t):
    """Brand gradient with slowly drifting 'molecule' bubbles."""
    img = Image.new("RGBA", (W, H))
    d = ImageDraw.Draw(img)
    for y in range(0, H, 4):
        k = y / H
        c = tuple(int(PRIMARY[i] * (1 - k) + PRIMARY_DARK[i] * k) for i in range(3))
        d.rectangle([0, y, W, y + 4], fill=c + (255,))
    layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    ld = ImageDraw.Draw(layer)
    for i in range(14):
        bx = (i * 173 + 60 * math.sin(t * 0.4 + i)) % W
        by = H - ((t * (30 + i * 6) + i * 260) % (H + 200)) + 100
        r = 18 + (i * 37) % 60
        ld.ellipse([bx - r, by - r, bx + r, by + r], outline=(255, 255, 255, 38), width=4)
    img.alpha_composite(layer)
    return img


# ---------------------------------------------------------------- scenes
# 0-4 hook | 4-14 ingredients | 14-23 steps | 23-27 tip | 27-30 CTA


def scene_alpha(t, a, b, fade=0.35):
    return clamp(min((t - a) / fade, (b - t) / fade))


def draw_frame(s, t):
    img = background(t)

    # top progress bar
    rrect(img, (60, 70, W - 60, 82), 6, WHITE, 0.25)
    rrect(img, (60, 70, 60 + (W - 120) * t / DUR, 82), 6, ACCENT)
    text_block(img, (60, 110), "BIONEXA", F_KICK, WHITE, 0.9)

    # HOOK
    a = scene_alpha(t, 0, 4)
    if a > 0:
        p = prog(t, 0.1, 0.6)
        text_block(img, (90, 600), s["kicker"], F_KICK, ACCENT, a * p)
        y = text_block(img, (90, 680 + 40 * (1 - p)), s["hook"], F_HOOK, WHITE, a * p, max_w=900)
        p2 = prog(t, 0.9, 0.5)
        rrect(img, (90, y + 40, 90 + 700 * p2, y + 52), 6, ACCENT, a)
        text_block(img, (90, y + 90), s["hook_sub"], F_SUB, TINT, a * p2)

    # INGREDIENTS
    a = scene_alpha(t, 4, 14)
    if a > 0:
        text_block(img, (90, 300), "Key ingredients", F_H2, WHITE, a)
        for i, (name, role) in enumerate(s["ingredients"]):
            p = prog(t, 4.6 + i * 1.6, 0.5)
            if p <= 0:
                continue
            y0 = 440 + i * 250
            x0 = 70 + 80 * (1 - p)
            rrect(img, (x0, y0, x0 + 940, y0 + 210), 34, WHITE, a * p * 0.96)
            rrect(img, (x0 + 30, y0 + 55, x0 + 130, y0 + 155), 50, PRIMARY, a * p)
            n = str(i + 1)
            dd = ImageDraw.Draw(img)
            text_block(img, (x0 + 80 - dd.textlength(n, font=F_NUM) / 2, y0 + 72), n, F_NUM, WHITE, a * p)
            text_block(img, (x0 + 165, y0 + 45), name, F_ITEM, INK, a * p, max_w=750)
            text_block(img, (x0 + 165, y0 + 115), role, F_SUB, MUTED, a * p, max_w=750)

    # STEPS
    a = scene_alpha(t, 14, 23)
    if a > 0:
        text_block(img, (90, 300), "The process", F_H2, WHITE, a)
        for i, step in enumerate(s["steps"]):
            p = prog(t, 14.5 + i * 2.0, 0.5)
            if p <= 0:
                continue
            y0 = 470 + i * 300
            rrect(img, (90, y0, 210, y0 + 120), 28, ACCENT, a * p)
            text_block(img, (128, y0 + 28), str(i + 1), F_NUM, INK, a * p)
            if i < len(s["steps"]) - 1:
                q = prog(t, 15.2 + i * 2.0, 0.8)
                rrect(img, (146, y0 + 135, 154, y0 + 135 + 150 * q), 4, WHITE, a * 0.4)
            text_block(img, (250 + 40 * (1 - p), y0 + 18), step, F_ITEM, WHITE, a * p, max_w=740)

    # TIP
    a = scene_alpha(t, 23, 27)
    if a > 0:
        p = prog(t, 23.1, 0.5)
        rrect(img, (70, 620, W - 70, 1300), 48, ACCENT, a * p)
        text_block(img, (140, 700), s["tip_title"].upper(), F_KICK, INK, a * p)
        text_block(img, (140, 790), s["tip"], F_HOOK if len(s["tip"]) < 45 else F_H2, INK, a * p, max_w=800)

    # CTA
    a = scene_alpha(t, 27, 30.5)
    if a > 0:
        p = prog(t, 27.1, 0.5)
        text_block(img, (0, 560), "Get the FULL formula", F_CTA, WHITE, a * p, center=True)
        text_block(img, (0, 680), "Exact %, process, QC specs", F_SUB, TINT, a * p, center=True)
        text_block(img, (0, 735), "Instant download", F_SUB, TINT, a * p, center=True)
        pulse = 1 + 0.03 * math.sin(t * 8)
        bw, bh = 760 * pulse, 150 * pulse
        rrect(img, ((W - bw) / 2, 900, (W + bw) / 2, 900 + bh), 75, ACCENT, a * p)
        text_block(img, (0, 900 + bh / 2 - 40), WEBSITE, F_CTA, INK, a * p, center=True)
        text_block(img, (0, 1150), "Link in description  •  Subscribe", F_SUB, WHITE, a * p, center=True)

    return img.convert("RGB")


def render(s):
    os.makedirs(OUT, exist_ok=True)
    path = os.path.join(OUT, f"{s['key']}.mp4")
    cmd = [
        "ffmpeg", "-y", "-loglevel", "error",
        "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{W}x{H}", "-r", str(FPS), "-i", "-",
        "-c:v", "libx264", "-preset", "medium", "-crf", "20", "-pix_fmt", "yuv420p",
        "-movflags", "+faststart", path,
    ]
    proc = subprocess.Popen(cmd, stdin=subprocess.PIPE)
    for f in range(int(DUR * FPS)):
        proc.stdin.write(draw_frame(s, f / FPS).tobytes())
    proc.stdin.close()
    proc.wait()
    print("wrote", path)


if __name__ == "__main__":
    keys = sys.argv[1:]
    for s in SHORTS:
        if not keys or s["key"] in keys:
            render(s)

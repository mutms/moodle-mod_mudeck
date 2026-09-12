# Slide deck for Moodle™ LMS

Make slide presentations right inside your Moodle course. No PowerPoint, no uploads,
no special software. You type your slides as plain text, pick a theme, and press
**Start presentation**.

Part of the [MuTMS suite](https://github.com/mutms), but it works on its own.

> **Status: unstable preview.** The plugin works and is tested, but it is young.
> Expect rough edges and changes between versions, and do not rely on it for a
> course you cannot afford to rebuild. Bug reports and ideas are welcome in the
> [issue tracker](https://github.com/mutms/moodle-mod_mudeck/issues).

## You don't need to know Markdown

Slides are written in a simple text style called Markdown. If you have never heard of it,
don't worry. Here is nearly everything you need:

| To get…          | Type…                          |
|------------------|--------------------------------|
| a slide title    | `# My title`                   |
| a smaller heading| `## My subtitle`               |
| a bullet point   | `- Sunlight`                   |
| bold text        | `**important**`                |
| a picture        | `![](photo.jpg)`               |
| a new slide      | `---` on a line by itself      |

That's it. Plain sentences stay plain sentences. Blank lines separate paragraphs.

## A short example

```markdown
# Photosynthesis
## How plants turn light into sugar

---

## The inputs

- Sunlight
- Water
- Carbon dioxide

---

## Why it matters

Every breath you take started as a **leaf**.
```

Three slides. Copy it into a new activity, press **Start presentation**, and see.

## Handy extras

* **Pictures** — upload them with your slides, then use their file name:
  `![](cat.jpg)`. For a full-slide background, write `![bg](cat.jpg)`.
* **Speaker notes** — anything between `<!--` and `-->` is shown only to you,
  never to students: `<!-- Ask them to guess the third one. -->`
* **Themes** — choose one when you set up the activity. Your site may offer extra ones.
* **Maths** — put a formula between dollar signs: `$x^2$` inside a sentence, or
  `$$ ... $$` on lines of its own for a big one.
* **Code** — a fenced block with the language name after the backticks is coloured:
  ` ```php `.
* **Diagrams** — a fenced block named `mermaid` becomes a diagram.
* **Colours** — `<!-- _backgroundColor: #123456 -->` or `<!-- _color: white -->` on a
  slide changes just that slide.

## Presenting

Open the activity and press **Start presentation**. Move with the arrow keys, the space bar,
a swipe, or a click near the edge of the slide. Click the slide number to see all slides
at once and jump to any of them. There is a full screen button, and your browser's print
gives you a PDF.

If you like, your phone or tablet can follow along with the presentation you are running
from your computer.

## Good to know

* Everything is one text file, so you can search it, reorder slides by cut and paste,
  and download or upload a whole presentation as a zip.
* Students see the slides but not your notes. You can let them see the notes later if you
  want the deck to serve as revision material.
* It is safe to let students write slides too. Nothing an author types can break the page.
* Screen readers work well, because slides are real web pages, not pictures of text.
* Already using [Marp](https://marp.app/) in VS Code? The same files work here, both ways.

## Requirements

Moodle 5.3 or later. No other plugins.

## Credits

Slide rendering by [Marp](https://marp.app/) (MIT), maths by
[MathJax](https://www.mathjax.org/) (Apache 2.0), code colouring by
[Shiki](https://shiki.style/) (MIT), diagrams by
[beautiful-mermaid](https://github.com/lukilabs/beautiful-mermaid) (MIT), sanitisation
by [DOMPurify](https://github.com/cure53/DOMPurify) (MPL 2.0).

## AI disclosure

parts of this project was written with the help of Claude (Anthropic). A human
maintainer reviewed, corrected and accepted everything before it was committed.
The design decisions and the final code are the maintainers' own.

## License

Copyright (C) 2026 Petr Skoda. [GPL-3.0](LICENSE) or later.

---

> MuTMS is an independent open-source project, not affiliated with Moodle HQ.

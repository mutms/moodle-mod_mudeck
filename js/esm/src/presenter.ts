// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Deck runtime on top of marp-core output: shows one slide at a time, scales it to the
 * viewport, and handles keyboard, swipe and edge tap input. Plain DOM; the chrome is React.
 *
 * @module     mod_mudeck/presenter
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Marp slide size, used until the real size can be measured. */
const FALLBACKWIDTH = 1280;
const FALLBACKHEIGHT = 720;

/** Minimum travel in pixels for a swipe rather than a tap. */
const SWIPEDISTANCE = 50;

/** Width of the tap zone on each edge, as a fraction of the deck. */
const TAPZONE = 0.25;

/** What a slide gets without a transition directive of its own. */
const DEFAULTTRANSITION = 'fade';

type ViewTransitionLike = {
    finished: Promise<void>;
    skipTransition: () => void;
};

/** The View Transitions API where the browser has it. */
const startViewTransition = (document as Document & {startViewTransition?: (change: () => void) => ViewTransitionLike})
    .startViewTransition?.bind(document);

/**
 * What an element is, for matching it across two slides.
 *
 * @param el
 * @return a key, or null for elements not worth matching
 */
const sharedKey = (el: Element): string | null => {
    if (el instanceof HTMLImageElement) {
        const src = el.getAttribute('src');
        return src ? `img:${src}` : null;
    }
    const text = el.textContent?.trim();
    return text ? `${el.tagName}:${text}` : null;
};

/**
 * Name the pictures and headings two slides share, so the browser morphs them rather than fading.
 *
 * @param from the slide on screen
 * @param to the slide about to be shown
 * @return the elements named, to be unnamed once the transition is over
 */
const tagShared = (from: HTMLElement, to: HTMLElement): HTMLElement[] => {
    const candidates = (slide: HTMLElement) => Array.from(slide.querySelectorAll<HTMLElement>('img, h1, h2, h3'));
    const before = new Map<string, HTMLElement>();
    for (const el of candidates(from)) {
        const key = sharedKey(el);
        if (key && !before.has(key)) {
            before.set(key, el);
        }
    }
    const tagged: HTMLElement[] = [];
    const used = new Set<string>();
    for (const el of candidates(to)) {
        const key = sharedKey(el);
        const match = key ? before.get(key) : undefined;
        if (!key || !match || used.has(key)) {
            continue;
        }
        used.add(key);
        const name = `mudeck-shared-${tagged.length}`;
        match.style.setProperty('view-transition-name', name);
        el.style.setProperty('view-transition-name', name);
        tagged.push(match, el);
    }
    return tagged;
};

export type PresenterState = {
    current: number;
    total: number;
};

export type Presenter = {
    next: () => void;
    previous: () => void;
    goto: (index: number) => void;
    toggleFullscreen: () => void;
    focus: () => void;
    titles: () => string[];
    destroy: () => void;
};

/**
 * Whether the target is an interactive control rather than the slide.
 *
 * @param target
 * @return bool
 */
const isInteractive = (target: EventTarget | null): boolean => {
    const el = target instanceof Element ? target.closest('a,button,input,select,textarea,summary,[role=button]') : null;
    return el !== null;
};

/**
 * Whether the target is a text entry, the only kind of element that may swallow the arrow keys.
 *
 * A focused button must not, or pressing the fullscreen control would leave the deck unresponsive.
 *
 * @param target
 * @return bool
 */
const isTextEntry = (target: EventTarget | null): boolean => {
    if (!(target instanceof Element)) {
        return false;
    }
    return target.closest('input,textarea,select,[contenteditable=""],[contenteditable=true]') !== null;
};

/**
 * Whether the target is a button or link that space and enter belong to.
 *
 * @param target
 * @return bool
 */
const isActivatable = (target: EventTarget | null): boolean => {
    if (!(target instanceof Element)) {
        return false;
    }
    return target.closest('a,button,summary,[role=button]') !== null;
};

/**
 * Show one slide at a time, scaled to fit, with keyboard, swipe and tap navigation.
 *
 * @param container element holding the rendered slides
 * @param onState called whenever the shown slide changes
 * @return controller used by the surrounding chrome
 */
export function mountPresenter(container: HTMLElement, onState: (state: PresenterState) => void): Presenter {
    const slides = Array.from(container.querySelectorAll<HTMLElement>('section'));
    if (!slides.length) {
        return {
            next: () => undefined,
            previous: () => undefined,
            "goto": () => undefined,
            toggleFullscreen: () => undefined,
            focus: () => undefined,
            titles: () => [],
            destroy: () => undefined,
        };
    }

    let index = 0;
    let running: ViewTransitionLike | null = null;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Marp slides have a fixed pixel size, so scale that box into the container.
    const fit = () => {
        const slide = slides[index];
        const width = slide.offsetWidth || FALLBACKWIDTH;
        const height = slide.offsetHeight || FALLBACKHEIGHT;
        const scale = Math.min(container.clientWidth / width, container.clientHeight / height);
        container.style.setProperty('--mudeck-scale', String(scale > 0 ? scale : 1));
        container.style.setProperty('--mudeck-slide-width', `${width}px`);
        container.style.setProperty('--mudeck-slide-height', `${height}px`);
    };

    const show = (next: number) => {
        const target = Math.max(0, Math.min(slides.length - 1, next));
        const change = () => {
            index = target;
            slides.forEach((slide, i) => {
                slide.hidden = i !== index;
            });
            fit();
            onState({current: index + 1, total: slides.length});
        };
        const kind = slides[target].dataset.transition ?? DEFAULTTRANSITION;
        if (target === index || kind === 'none' || reducedMotion.matches || !startViewTransition) {
            change();
            return;
        }
        // A transition still running is finished at once; navigating fast must not queue up.
        running?.skipTransition();
        const root = document.documentElement;
        root.dataset.mudeckTransition = kind;
        root.dataset.mudeckDirection = target > index ? 'forward' : 'back';
        const shared = tagShared(slides[index], slides[target]);
        const transition = startViewTransition(change);
        running = transition;
        transition.finished.finally(() => {
            shared.forEach((el) => el.style.removeProperty('view-transition-name'));
            if (running === transition) {
                delete root.dataset.mudeckTransition;
                delete root.dataset.mudeckDirection;
                running = null;
            }
        });
    };

    const showAndFit = show;

    const onKey = (event: KeyboardEvent) => {
        if (isTextEntry(event.target)) {
            return;
        }
        switch (event.key) {
            case ' ':
                // Space activates a focused button or link.
                if (isActivatable(event.target)) {
                    return;
                }
                showAndFit(index + 1);
                event.preventDefault();
                break;
            case 'ArrowRight':
            case 'PageDown':
                showAndFit(index + 1);
                event.preventDefault();
                break;
            case 'ArrowLeft':
            case 'PageUp':
                showAndFit(index - 1);
                event.preventDefault();
                break;
            case 'Home':
                showAndFit(0);
                event.preventDefault();
                break;
            case 'End':
                showAndFit(slides.length - 1);
                event.preventDefault();
                break;
            default:
                break;
        }
    };

    // Pointer events cover mouse, touch and pen: a horizontal drag or an edge tap moves a slide.
    let startx = 0;
    let starty = 0;
    let dragging = false;
    const onPointerDown = (event: PointerEvent) => {
        if (isInteractive(event.target)) {
            return;
        }
        dragging = true;
        startx = event.clientX;
        starty = event.clientY;
    };
    const onPointerUp = (event: PointerEvent) => {
        if (!dragging || isInteractive(event.target)) {
            dragging = false;
            return;
        }
        dragging = false;
        const dx = event.clientX - startx;
        const dy = event.clientY - starty;
        if (Math.abs(dx) > SWIPEDISTANCE && Math.abs(dx) > Math.abs(dy)) {
            showAndFit(dx < 0 ? index + 1 : index - 1);
            return;
        }
        if (Math.abs(dx) < SWIPEDISTANCE && Math.abs(dy) < SWIPEDISTANCE) {
            const rect = container.getBoundingClientRect();
            const relative = (event.clientX - rect.left) / rect.width;
            if (relative <= TAPZONE) {
                showAndFit(index - 1);
            } else if (relative >= 1 - TAPZONE) {
                showAndFit(index + 1);
            }
        }
    };

    const onResize = () => fit();

    showAndFit(0);
    // The first measure can be too early; fit again once fonts and theme CSS have settled.
    window.setTimeout(fit, 100);
    document.addEventListener('keydown', onKey);
    window.addEventListener('resize', onResize);
    document.addEventListener('fullscreenchange', onResize);
    container.addEventListener('pointerdown', onPointerDown, {passive: true});
    container.addEventListener('pointerup', onPointerUp, {passive: true});

    return {
        next: () => showAndFit(index + 1),
        previous: () => showAndFit(index - 1),
        "goto": (target: number) => showAndFit(target - 1),
        focus: () => container.focus({preventScroll: true}),
        titles: () => slides.map((slide, i) => {
            const heading = slide.querySelector('h1,h2,h3,h4')?.textContent?.trim();
            return heading || String(i + 1);
        }),
        toggleFullscreen: () => {
            const root = container.closest('.mudeck-deck') ?? container;
            if (!document.fullscreenElement) {
                root.requestFullscreen?.();
            } else {
                document.exitFullscreen?.();
            }
        },
        destroy: () => {
            document.removeEventListener('keydown', onKey);
            window.removeEventListener('resize', onResize);
            document.removeEventListener('fullscreenchange', onResize);
            container.removeEventListener('pointerdown', onPointerDown);
            container.removeEventListener('pointerup', onPointerUp);
        },
    };
}

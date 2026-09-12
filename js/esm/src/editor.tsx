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
 * Live slide preview beside the server-rendered edit form, whose textarea remains the only copy of the text.
 *
 * @module     mod_mudeck/editor
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useCallback, useEffect, useRef, useState} from 'react';
import {createPortal} from 'react-dom';
import {renderPart} from './render';
import {caretPoint} from './caret';
import {isBreak, opensBreak, slideEnd} from './source';

/** Session storage key for the split position. */
const SPLITKEY = 'mudeck-editor-split';

/** Minimum textarea height in pixels. */
const LEASTHEIGHT = 200;

/** Milliseconds a notification stays before it is removed. */
const NOTICEFOR = 5000;

/** Debounce delay in milliseconds before the preview is redrawn. */
const REDRAWAFTER = 250;

/** Invisible marker inserted at the caret to find the current slide in the rendered output. */
const MARKER = '⁠mudeckcaret⁠';

type Labels = {
    preview: string;
    slide: string;
    markdownhelp: string;
    mediahelp: string;
    media: string;
    mediaintro: string;
    help: string;
    fullscreen: string;
};

/** Server-rendered help HTML. */
type Help = {
    markdown: string;
    media: string;
};

type EditorProps = {
    themecss?: Record<string, string>;
    theme: string;
    labels: Labels;
    help: Help;
    imagesurl: string;
    mediabase: string;
};

/** Matches a target that is already absolute. */
const ABSOLUTE = /^([a-z][a-z0-9+.-]*:|\/\/|\/|#)/i;

/** Matches image and link targets in Markdown. */
const REFERENCE = /(!?\[[^\]]*\]\(\s*)([^)\s]+)/g;

/**
 * Rewrites relative media references to the form's draft file area.
 *
 * @param markdown the Markdown as written
 * @param base where the draft area is served from
 * @returns Markdown a browser can fetch the pictures of
 */
function withMedia(markdown: string, base: string): string {
    if (!base) {
        return markdown;
    }
    const home = base.replace(/\/+$/, '');
    return markdown
        .split('@@PLUGINFILE@@').join(home)
        .replace(REFERENCE, (whole, lead: string, target: string) => (
            ABSOLUTE.test(target) ? whole : `${lead}${home}/${target.replace(/^\/+/, '')}`
        ));
}

/** Matches "![" followed by unfinished alt text. */
const OPENALT = /!\[([^\]\n]*)$/;

/** Matches "![alt](" with no target yet. */
const OPENIMAGE = /!\[([^\]\n]*)\]\($/;

/** Caret position within an unfinished image and its alt text so far. */
type Offer = {
    alt: string;
    mode: 'alt' | 'parens';
};

/**
 * Detects whether the caret is inside an unfinished image, in its alt text or in empty parentheses.
 *
 * @param textarea the field being written in
 * @returns what is being written, or null when the caret is not in an unfinished image
 */
function offering(textarea: HTMLTextAreaElement): Offer | null {
    const caret = textarea.selectionStart ?? 0;
    if ((textarea.selectionEnd ?? 0) !== caret) {
        return null;
    }
    const before = textarea.value.slice(0, caret);

    const parens = before.match(OPENIMAGE);
    if (parens && textarea.value.charAt(caret) === ')') {
        return {alt: parens[1], mode: 'parens'};
    }
    const alt = before.match(OPENALT);
    if (!alt) {
        return null;
    }
    // An image that already names a file is being edited, not written.
    return /^[^\]\n]*\]\(/.test(textarea.value.slice(caret)) ? null : {alt: alt[1], mode: 'alt'};
}

/** Matches a Markdown heading line. */
const HEADING = /^[ \t]{0,3}#{1,6}[ \t]/;

/**
 * Appends the marker to a heading of the slide containing the caret.
 *
 * A heading is used because trailing text cannot break it and it always reaches the rendered slide.
 *
 * @param text the Markdown as written
 * @param caret where the writing cursor is
 * @returns the text with a marker in it, or null when there was no heading to mark
 */
function withMarker(text: string, caret: number): string | null {
    const lines = text.split('\n');
    let start = 0;
    let index = 0;
    for (; index < lines.length; index++) {
        const end = start + lines[index].length;
        if (caret <= end) {
            break;
        }
        start = end + 1;
    }

    const mark = (at: number) => {
        const marked = [...lines];
        marked[at] = `${marked[at]}${MARKER}`;
        return marked.join('\n');
    };

    // The isBreak helper handles code fences and setext underlines the same way Marp does.
    const breaks = lines.map((line, at) => isBreak(line, at === 0 || opensBreak(lines[at - 1]), null));

    const from = Math.min(index, lines.length - 1);
    for (let at = from; at >= 0; at--) {
        if (breaks[at]) {
            break;
        }
        if (HEADING.test(lines[at])) {
            return mark(at);
        }
    }
    for (let at = from; at < lines.length; at++) {
        if (breaks[at]) {
            break;
        }
        if (HEADING.test(lines[at])) {
            return mark(at);
        }
    }
    return null;
}

/**
 * Moves the caret and scrolls it into the middle of the field.
 *
 * @param textarea the field being written in
 * @param from where the selection starts
 * @param to where it ends; the same number for a plain cursor
 */
function showCaret(textarea: HTMLTextAreaElement, from: number, to: number): void {
    textarea.focus();
    textarea.setSelectionRange(from, to);
    const point = caretPoint(textarea, to);
    const box = textarea.getBoundingClientRect();
    textarea.scrollTop += point.top - box.top - textarea.clientHeight / 2;
}

/**
 * Inserts text as typing would, so undo and input listeners keep working.
 *
 * @param textarea the field being written in
 * @param from where the change starts
 * @param to where it ends
 * @param text what goes there
 * @param caret where to leave the cursor
 */
function write(textarea: HTMLTextAreaElement, from: number, to: number, text: string, caret: number): void {
    textarea.focus();
    textarea.setSelectionRange(from, to);
    // Only execCommand makes the browser record an undoable edit.
    if (!document.execCommand('insertText', false, text)) {
        textarea.setRangeText(text, from, to, 'end');
        textarea.dispatchEvent(new Event('input', {bubbles: true}));
    }
    textarea.setSelectionRange(caret, caret);
}

export default function Editor({themecss, theme, labels, help, imagesurl, mediabase}: EditorProps) {
    const stripref = useRef<HTMLOListElement>(null);
    const mediaref = useRef<HTMLDivElement>(null);
    const menuref = useRef<HTMLUListElement>(null);
    const [split, setSplit] = useState(() => {
        try {
            return Number(window.sessionStorage.getItem(SPLITKEY)) || 40;
        } catch {
            return 40;
        }
    });
    const [tab, setTab] = useState<'preview' | 'media' | 'help'>('preview');
    const [slides, setSlides] = useState<string[]>([]);
    const [css, setCss] = useState('');
    const [current, setCurrent] = useState(0);
    const [full, setFull] = useState(false);
    const [menu, setMenu] = useState<{items: string[]; left: number; top: number} | null>(null);
    // Nothing is selected until an arrow key selects it, so Enter keeps inserting a new line.
    const [pick, setPick] = useState(-1);
    const dismissed = useRef(false);
    // Cached so key handling never waits for the network.
    const files = useRef<string[]>([]);

    /** Fetches the list of uploaded files. */
    const load = useCallback(async() => {
        try {
            const answer = await fetch(imagesurl, {headers: {Accept: 'application/json'}});
            files.current = (await answer.json())?.files ?? [];
        } catch {
            files.current = [];
        }
    }, [imagesurl]);

    /** Shows the file menu beside the caret, or hides it. */
    const offer = useCallback((textarea: HTMLTextAreaElement, open: boolean) => {
        const spot = offering(textarea);
        if (!spot) {
            dismissed.current = false;
            setMenu(null);
            return;
        }
        if ((dismissed.current && !open) || !files.current.length) {
            return;
        }
        dismissed.current = false;

        const point = caretPoint(textarea, textarea.selectionStart ?? 0);
        const items = files.current;
        // Reset the selection only when the menu opens.
        setMenu((was) => {
            if (!was) {
                setPick(-1);
            }
            return {items, left: point.left, top: point.top + point.height};
        });
    }, []);

    /** Completes the image with the chosen file name. */
    const choose = useCallback((name: string) => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        const spot = textarea ? offering(textarea) : null;
        if (!textarea || !spot) {
            return;
        }
        const at = textarea.selectionStart ?? 0;

        if (spot.mode === 'parens') {
            // With no alt text yet the caret goes back between the square brackets.
            const start = at - `![${spot.alt}](`.length;
            write(textarea, at, at, name, spot.alt === '' ? start + 2 : at + name.length + 1);
            setMenu(null);
            return;
        }

        // Close the alt text and add the file; the caret stays in empty brackets or moves after the image.
        const text = `](${name})`;
        write(textarea, at, at, text, spot.alt === '' ? at : at + text.length);
        setMenu(null);
    }, []);

    /** Sequence number so a slow redraw cannot overwrite a newer one. */
    const drawing = useRef(0);

    /** Renders the slides and finds the one containing the caret. */
    const redraw = useCallback(async(textarea: HTMLTextAreaElement) => {
        const text = textarea.value;
        const marked = withMarker(text, textarea.selectionStart ?? 0);
        const turn = ++drawing.current;
        const {html, css: slidecss, notes} = await renderPart(withMedia(marked ?? text, mediabase), theme, themecss ?? {});
        if (turn !== drawing.current) {
            return;
        }

        const holder = document.createElement('div');
        holder.innerHTML = html;
        const sections = Array.from(holder.querySelectorAll('section'));

        let found = -1;
        sections.forEach((section, index) => {
            if (section.innerHTML.includes(MARKER)) {
                found = index;
                section.innerHTML = section.innerHTML.split(MARKER).join('');
            }
        });
        if (found < 0) {
            // A marker inside a speaker note ends up in the notes, not the slide.
            found = notes.findIndex((note) => note.includes(MARKER));
        }

        setSlides(sections.map((section) => section.outerHTML));
        setCss(slidecss);
        // No marker means no heading; keep the current slide.
        if (found >= 0) {
            setCurrent(found);
        }
    }, [theme, themecss, mediabase]);

    // Save and continue reloads the page, so the caret position travels with the form submission.
    useEffect(() => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        const form = textarea?.closest('form');
        if (!textarea || !form) {
            return undefined;
        }
        // A form exposes its own fields as properties, so they are asked for by name.
        const start = form.elements.namedItem('caretstart') as HTMLInputElement | null;
        const end = form.elements.namedItem('caretend') as HTMLInputElement | null;

        const remember = () => {
            if (start && end) {
                start.value = String(textarea.selectionStart ?? 0);
                end.value = String(textarea.selectionEnd ?? 0);
            }
        };
        form.addEventListener('submit', remember);
        return () => form.removeEventListener('submit', remember);
    }, []);

    // Runs before the first redraw below, which reads the caret to pick the slide.
    useEffect(() => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        const form = textarea?.closest('form');
        if (!textarea || !form) {
            return undefined;
        }
        const from = Number((form.elements.namedItem('caretstart') as HTMLInputElement | null)?.value ?? 0);
        const to = Number((form.elements.namedItem('caretend') as HTMLInputElement | null)?.value ?? 0);
        if (!to) {
            // A freshly opened editor has nothing to restore, and must not steal focus.
            return undefined;
        }

        showCaret(textarea, from, to);
        return undefined;
    }, []);

    // The textarea takes whatever height the window has left below it.
    useEffect(() => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        if (!textarea) {
            return undefined;
        }
        const editor = textarea.closest<HTMLElement>('.mudeck-editor');
        const fit = () => {
            // Page coordinates, so a scrolled page does not read as spare room.
            const bottom = (editor ?? textarea).getBoundingClientRect().bottom + window.scrollY;
            const spare = window.innerHeight - bottom;
            const height = textarea.getBoundingClientRect().height + spare;
            textarea.style.height = `${Math.max(LEASTHEIGHT, height)}px`;
        };
        fit();
        // Web fonts and the file manager arrive late and move the bottom of the form.
        const settled = window.requestAnimationFrame(fit);
        window.addEventListener('resize', fit);
        return () => {
            window.cancelAnimationFrame(settled);
            window.removeEventListener('resize', fit);
        };
    }, []);

    useEffect(() => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        if (!textarea) {
            return undefined;
        }

        let timer: number | undefined;
        const later = () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => redraw(textarea), REDRAWAFTER);
        };

        redraw(textarea);
        const events: (keyof HTMLElementEventMap)[] = ['input', 'keyup', 'click', 'focus'];
        events.forEach((name) => textarea.addEventListener(name, later));
        return () => {
            events.forEach((name) => textarea.removeEventListener(name, later));
            window.clearTimeout(timer);
        };
    }, [redraw]);

    // The upload list follows the caret inside an unfinished image; arrow keys select, Enter writes.
    useEffect(() => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        if (!textarea) {
            return undefined;
        }

        const onkey = (event: KeyboardEvent) => {
            const last = menu ? menu.items.length - 1 : -1;
            if (event.key === 'ArrowDown') {
                if (!menu) {
                    // ArrowDown opens the menu only inside an unfinished image with files to offer.
                    if (files.current.length && offering(textarea)) {
                        event.preventDefault();
                        offer(textarea, true);
                    }
                    return;
                }
                event.preventDefault();
                setPick((was) => (was >= last ? 0 : was + 1));
            } else if (menu && event.key === 'ArrowUp') {
                event.preventDefault();
                setPick((was) => (was <= 0 ? last : was - 1));
            } else if (menu && pick >= 0 && (event.key === 'Enter' || event.key === 'Tab')) {
                event.preventDefault();
                choose(menu.items[pick]);
            } else if (menu && event.key === 'Escape') {
                event.preventDefault();
                dismissed.current = true;
                setMenu(null);
            }
        };

        const follow = () => offer(textarea, false);

        const away = (event: Event) => {
            if (!menuref.current?.contains(event.target as Node)) {
                setMenu(null);
            }
        };
        const close = () => setMenu(null);

        const watched: (keyof HTMLElementEventMap)[] = ['input', 'keyup', 'click'];
        textarea.addEventListener('keydown', onkey);
        watched.forEach((name) => textarea.addEventListener(name, follow));
        textarea.addEventListener('scroll', close);
        window.addEventListener('resize', close);
        document.addEventListener('pointerdown', away);
        return () => {
            textarea.removeEventListener('keydown', onkey);
            watched.forEach((name) => textarea.removeEventListener(name, follow));
            textarea.removeEventListener('scroll', close);
            window.removeEventListener('resize', close);
            document.removeEventListener('pointerdown', away);
        };
    }, [menu, pick, offer, choose]);

    useEffect(() => {
        menuref.current
            ?.querySelector('[aria-selected="true"]')
            ?.scrollIntoView({block: 'nearest'});
    }, [pick, menu]);

    // Session storage only: Save and continue reloads the page and must keep the layout.
    useEffect(() => {
        document.documentElement.style.setProperty('--mudeck-split', `${split}%`);
        try {
            window.sessionStorage.setItem(SPLITKEY, String(split));
        } catch {
            // Storage may be unavailable.
        }
    }, [split]);

    // Slides are scaled into the strip, so the strip must know its own width.
    useEffect(() => {
        const strip = stripref.current;
        if (!strip) {
            return undefined;
        }
        const fit = () => {
            const box = strip.querySelector('.mudeck-editor-slide-box');
            // A hidden pane measures zero, which would scale every slide to nothing.
            if (box && box.clientWidth > 0) {
                strip.style.setProperty('--mudeck-strip-width', String(box.clientWidth));
            }
        };
        fit();
        const watcher = new ResizeObserver(fit);
        watcher.observe(strip);
        return () => watcher.disconnect();
    }, [slides, tab]);

    // Moodle may insert or replace notifications after load, so the whole body is observed.
    useEffect(() => {
        let fade: number | undefined;
        let clear: number | undefined;
        let showing = '';

        const expire = () => {
            const notices = document.querySelector('#user-notifications');
            const text = notices?.textContent?.trim() ?? '';
            if (!text) {
                showing = '';
                return;
            }
            // Only a new message restarts the timer, or constant redraws would keep it on screen.
            if (text === showing) {
                return;
            }
            showing = text;
            window.clearTimeout(fade);
            window.clearTimeout(clear);
            fade = window.setTimeout(() => notices?.classList.add('mudeck-notification-going'), NOTICEFOR);
            clear = window.setTimeout(() => {
                if (notices) {
                    notices.innerHTML = '';
                    notices.classList.remove('mudeck-notification-going');
                }
                showing = '';
            }, NOTICEFOR + 500);
        };

        expire();
        const watcher = new MutationObserver(expire);
        watcher.observe(document.body, {childList: true, subtree: true});
        return () => {
            watcher.disconnect();
            window.clearTimeout(fade);
            window.clearTimeout(clear);
        };
    }, []);

    // Moodle's file manager is moved here and put back on unmount so the form still submits it.
    useEffect(() => {
        const holder = mediaref.current;
        const field = document.querySelector<HTMLElement>('#fitem_id_attachments');
        if (!holder || !field) {
            return undefined;
        }
        const home = field.parentElement;
        const next = field.nextElementSibling;
        const form = field.closest('form');
        holder.appendChild(field);

        // The form attribute keeps moved inputs submitted; getAttribute, since a field named "id" shadows form.id.
        const formid = form?.getAttribute('id');
        if (formid) {
            field.querySelectorAll('input, select, textarea').forEach((input) => {
                input.setAttribute('form', formid);
            });
        }

        return () => {
            if (next) {
                home?.insertBefore(field, next);
            } else {
                home?.appendChild(field);
            }
        };
    }, []);

    // The browser can leave fullscreen on its own, so the state follows the event.
    useEffect(() => {
        const watch = () => setFull(document.fullscreenElement !== null);
        watch();
        document.addEventListener('fullscreenchange', watch);
        return () => document.removeEventListener('fullscreenchange', watch);
    }, []);

    // The button is portalled into the slot beside the server-rendered title.
    const [tools, setTools] = useState<HTMLElement | null>(null);
    useEffect(() => {
        setTools(document.querySelector<HTMLElement>('[data-region=mudeck-editor-tools]'));
    }, []);

    const toggleFull = () => {
        if (document.fullscreenElement) {
            void document.exitFullscreen();
        } else {
            void document.documentElement.requestFullscreen();
        }
    };

    // Reloaded whenever the file manager changes.
    useEffect(() => {
        void load();
        const holder = mediaref.current;
        if (!holder) {
            return undefined;
        }
        let timer: number | undefined;
        const later = () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => void load(), REDRAWAFTER);
        };
        const watcher = new MutationObserver(later);
        watcher.observe(holder, {childList: true, subtree: true});
        return () => {
            watcher.disconnect();
            window.clearTimeout(timer);
        };
    }, [load]);

    useEffect(() => {
        stripref.current
            ?.querySelector(`[data-slide="${current}"]`)
            ?.scrollIntoView({block: 'center', behavior: 'smooth'});
    }, [current, slides]);

    /**
     * Moves the caret to the end of the clicked slide.
     *
     * @param index 0-based position in the strip
     */
    const goToSlide = (index: number) => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        if (!textarea) {
            return;
        }
        const at = slideEnd(textarea.value, index + 1);
        showCaret(textarea, at, at);
        // Moving the caret from code fires no event.
        redraw(textarea);
    };

    /** Resizes the split by dragging the divider. */
    const drag = (event: React.PointerEvent<HTMLDivElement>) => {
        event.currentTarget.setPointerCapture(event.pointerId);
        const move = (moving: PointerEvent) => {
            const share = ((window.innerWidth - moving.clientX) / window.innerWidth) * 100;
            setSplit(Math.min(70, Math.max(20, share)));
        };
        const stop = () => {
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', stop);
        };
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', stop);
    };

    const fullbutton = (
        <button
            type="button"
            className="btn btn-sm btn-secondary mudeck-editor-full"
            title={labels.fullscreen}
            aria-pressed={full}
            onClick={toggleFull}
        >
            <i className={`fa fa-${full ? 'compress' : 'expand'}`} aria-hidden="true" />
            <span className="visually-hidden">{labels.fullscreen}</span>
        </button>
    );

    return (
        <>
            {tools && createPortal(fullbutton, tools)}

            <div
                className="mudeck-editor-divider"
                role="separator"
                aria-orientation="vertical"
                aria-label={labels.preview}
                aria-valuenow={Math.round(split)}
                tabIndex={0}
                onPointerDown={drag}
                onKeyDown={(event) => {
                    if (event.key === 'ArrowLeft') {
                        setSplit((was) => Math.min(70, was + 2));
                    } else if (event.key === 'ArrowRight') {
                        setSplit((was) => Math.max(20, was - 2));
                    }
                }}
            />

            {menu && (
                <ul
                    className="mudeck-editor-menu"
                    ref={menuref}
                    role="listbox"
                    aria-label={labels.media}
                    data-region="mudeck-editor-menu"
                    style={{left: `${menu.left}px`, top: `${menu.top}px`}}
                >
                    {menu.items.map((name, index) => (
                        <li
                            key={name}
                            role="option"
                            aria-selected={index === pick}
                            className={`mudeck-editor-menu-item${index === pick ? ' mudeck-editor-menu-current' : ''}`}
                            /* Keep focus in the textarea, or the menu would close before the click. */
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => choose(name)}
                        >
                            {name}
                        </li>
                    ))}
                </ul>
            )}

        <div className="mudeck-editor-preview" data-region="mudeck-editor-preview">
            <style>{css}</style>

            <ul className="nav nav-underline mudeck-editor-tabs" role="tablist">
                {([
                    ['preview', labels.preview],
                    ['media', labels.media],
                    ['help', labels.help],
                ] as const).map(([name, label]) => (
                    <li className="nav-item" key={name} role="presentation">
                        <button
                            type="button"
                            role="tab"
                            aria-selected={tab === name}
                            className={`nav-link${tab === name ? ' active' : ''}`}
                            onClick={() => setTab(name)}
                        >
                            {label}
                        </button>
                    </li>
                ))}
            </ul>

            <div className="mudeck-editor-pane" hidden={tab !== 'preview'}>
                <ol className="mudeck-editor-strip" ref={stripref}>
                    {slides.map((slide, index) => (
                        <li
                            key={index}
                            data-slide={index}
                            className={`mudeck-editor-slide${index === current ? ' mudeck-editor-slide-current' : ''}`}
                            aria-current={index === current}
                        >
                            {/* Marp scopes its CSS to "div.marpit > section"; a div cannot sit inside a button. */}
                            <div className="mudeck-editor-slide-box marpit" dangerouslySetInnerHTML={{__html: slide}} />
                            <button
                                type="button"
                                className="mudeck-editor-slide-jump"
                                title={labels.slide.replace('{$a}', String(index + 1))}
                                onClick={() => goToSlide(index)}
                            >
                                <span className="visually-hidden">
                                    {labels.slide.replace('{$a}', String(index + 1))}
                                </span>
                            </button>
                            <span className="mudeck-editor-number">
                                {labels.slide.replace('{$a}', String(index + 1))}
                            </span>
                        </li>
                    ))}
                </ol>
            </div>

            <div className="mudeck-editor-pane" hidden={tab !== 'media'}>
                <div ref={mediaref} />
                <p className="text-muted mudeck-editor-intro">{labels.mediaintro}</p>
            </div>

            <div className="mudeck-editor-pane" hidden={tab !== 'help'}>
                <h2 className="mudeck-editor-heading">{labels.markdownhelp}</h2>
                <div dangerouslySetInnerHTML={{__html: help?.markdown ?? ''}} />
                <h2 className="mudeck-editor-heading">{labels.mediahelp}</h2>
                <div dangerouslySetInnerHTML={{__html: help?.media ?? ''}} />
            </div>
        </div>
        </>
    );
}

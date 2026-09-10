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
 * A preview beside the slides being written.
 *
 * The Moodle form is left exactly as the server rendered it and stays the only copy of
 * the text: this draws the slides next to it and follows the writing cursor. If it never
 * mounts, the page is still the plain form it always was.
 *
 * @module     mod_mudeck/editor
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useCallback, useEffect, useRef, useState} from 'react';
import {createPortal} from 'react-dom';
import {renderPart} from './render';
import {caretPoint} from './caret';
import {isBreak, opensBreak, slideEnd} from './source';

/** Where the split is kept, so saving and carrying on does not throw the layout away. */
const SPLITKEY = 'mudeck-editor-split';

/** The least room worth writing in, roughly a phone screen. */
const LEASTHEIGHT = 200;

/** How long a "saved" notification stays before it takes itself away. */
const NOTICEFOR = 5000;

/** How long the typing has to pause before the preview is redrawn. */
const REDRAWAFTER = 250;

/** Invisible, harmless in every context, and easy to find again in the rendered slides. */
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

/** Help as the server rendered it, ready to sit open beside the writing. */
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

/** A reference that already points somewhere on its own. */
const ABSOLUTE = /^([a-z][a-z0-9+.-]*:|\/\/|\/|#)/i;

/** Every image and link target in the Markdown. */
const REFERENCE = /(!?\[[^\]]*\]\(\s*)([^)\s]+)/g;

/**
 * Point the file names at the files, the way the server does before a saved part is shown.
 *
 * While a part is being written its pictures live in the form's draft area - uploaded
 * moments ago and not saved anywhere yet - so the preview reads them from there.
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

/** An image being described: "![" then the alt text being typed, no closing bracket yet. */
const OPENALT = /!\[([^\]\n]*)$/;

/** An image whose brackets are done but whose file name is missing: "![alt](" then ")". */
const OPENIMAGE = /!\[([^\]\n]*)\]\($/;

/** Where the cursor is in an unfinished image, and what has been said about it so far. */
type Offer = {
    alt: string;
    mode: 'alt' | 'parens';
};

/**
 * Is the cursor somewhere an uploaded picture could be named?
 *
 * Two places: writing the alt text of a new image, where the list stands by until it is
 * wanted, and inside empty brackets, where the file name is the only thing missing.
 * Brackets with a name already in them are somebody editing, and are left alone.
 *
 * @param textarea the field being written in
 * @returns what is being written, or null when this is none of our business
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
    // An image that already names a file is one being edited, not one being written.
    return /^[^\]\n]*\]\(/.test(textarea.value.slice(caret)) ? null : {alt: alt[1], mode: 'alt'};
}

/** Matches a heading, the one line that cannot be broken by adding something to its end. */
const HEADING = /^[ \t]{0,3}#{1,6}[ \t]/;

/**
 * Put the marker at the end of the heading the cursor is under.
 *
 * A heading is the safe place: trailing text cannot break it, unlike a table row, a
 * comment, a link or a separator, and it always reaches the rendered slide.
 *
 * The search runs backwards to the slide's own break, then forwards to the next one -
 * so a cursor just after a separator, or above the first heading of the text, finds the
 * heading of the slide it is in rather than the one before. A slide with no heading at
 * all marks nothing and the preview stays where it was, which the next keystroke
 * corrects anyway.
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

    // A break here means the same thing it means to Marp: the shared test knows about
    // code fences and about dashes that underline a line of prose instead of cutting it.
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
 * Put the cursor somewhere and make sure it can be seen.
 *
 * @param textarea the field being written in
 * @param from where the selection starts
 * @param to where it ends; the same number for a plain cursor
 */
function showCaret(textarea: HTMLTextAreaElement, from: number, to: number): void {
    textarea.focus();
    textarea.setSelectionRange(from, to);
    // Measured at the end, where the writing carries on, and put in the middle of the
    // field rather than against its bottom edge.
    const point = caretPoint(textarea, to);
    const box = textarea.getBoundingClientRect();
    textarea.scrollTop += point.top - box.top - textarea.clientHeight / 2;
}

/**
 * Put text in as typing would, so undo, the preview and Moodle's own watchers all follow.
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
    // The old way of writing is the only one the browser records as an edit, so undo
    // still works; when it is gone, the text is what matters and undo is a nicety.
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
    // Nothing is chosen until an arrow key chooses it, so Enter is still a new line and
    // typing is never interrupted by a list that thinks it knows better.
    const [pick, setPick] = useState(-1);
    const dismissed = useRef(false);
    // What has been uploaded, kept to hand: whether there is anything to offer has to be
    // answerable on the spot, or a key press would have to wait for the network to say
    // whether it belongs to the list or to the cursor.
    const files = useRef<string[]>([]);

    /** Ask what pictures the form holds now. */
    const load = useCallback(async () => {
        try {
            const answer = await fetch(imagesurl, {headers: {Accept: 'application/json'}});
            files.current = (await answer.json())?.files ?? [];
        } catch {
            // Nothing to offer. Saying so would interrupt the writing for no good reason.
            files.current = [];
        }
    }, [imagesurl]);

    /** Stand the list of uploaded pictures beside the cursor, or take it away. */
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
        // Opening starts with nothing chosen; already open, it only has to keep up.
        setMenu((was) => {
            if (!was) {
                setPick(-1);
            }
            return {items, left: point.left, top: point.top + point.height};
        });
    }, []);

    /** Finish the image with the chosen file - the only thing that ever writes anything. */
    const choose = useCallback((name: string) => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        const spot = textarea ? offering(textarea) : null;
        if (!textarea || !spot) {
            return;
        }
        const at = textarea.selectionStart ?? 0;

        if (spot.mode === 'parens') {
            // The brackets are already there and empty; only the name is missing. An image
            // nobody has described yet wants its alt text next, so the cursor goes back
            // between the square brackets; one that has a description is finished with.
            const start = at - `![${spot.alt}](`.length;
            write(textarea, at, at, name, spot.alt === '' ? start + 2 : at + name.length + 1);
            setMenu(null);
            return;
        }

        // Mid alt text: close it and add the file, leaving the cursor after the image -
        // or back in the empty brackets, which is where it already is.
        const text = `](${name})`;
        write(textarea, at, at, text, spot.alt === '' ? at : at + text.length);
        setMenu(null);
    }, []);

    /** Draw the slides as they stand, and work out which one is being written. */
    const redraw = useCallback((textarea: HTMLTextAreaElement) => {
        const text = textarea.value;
        const marked = withMarker(text, textarea.selectionStart ?? 0);
        const {html, css: slidecss, notes} = renderPart(withMedia(marked ?? text, mediabase), theme, themecss ?? {});

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
            // A marker written inside a speaker note never reaches the slide: Marp hands
            // those back as comments instead, which say which slide they belong to.
            found = notes.findIndex((note) => note.includes(MARKER));
        }

        setSlides(sections.map((section) => section.outerHTML));
        setCss(slidecss);
        // An unfound marker means the cursor was somewhere without a slide of its own,
        // and the next keystroke will say where it is - so stay where we are.
        if (found >= 0) {
            setCurrent(found);
        }
    }, [theme, themecss, mediabase]);

    // Saving without leaving answers the POST with the editor again, which would
    // otherwise put the cursor back at the very start of the text. So the position rides
    // along with the submission: read off the text area as the form goes, put back when
    // the saved text comes home. A text area remembers its selection after losing focus,
    // which is why one read at submit time is enough - the mouse press on the button
    // cannot disturb it.
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

    // Putting it back, before the preview is first drawn: the redraw below reads the
    // cursor to decide which slide to highlight, and effects run in the order they are
    // written, so by then the cursor is where the writer left it.
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

    // The writing area takes whatever the window has left below it, so a taller browser
    // means more text on screen rather than more empty space under the buttons.
    useEffect(() => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        if (!textarea) {
            return undefined;
        }
        const editor = textarea.closest<HTMLElement>('.mudeck-editor');
        const fit = () => {
            // How far the editor ends from the bottom of the window, in page coordinates so
            // that a scrolled page does not read as room to grow into. Whatever that gap
            // is, the writing area is the field that takes it - or gives it back - and the
            // margins the form puts around its fields never have to be guessed at.
            const bottom = (editor ?? textarea).getBoundingClientRect().bottom + window.scrollY;
            const spare = window.innerHeight - bottom;
            const height = textarea.getBoundingClientRect().height + spare;
            textarea.style.height = `${Math.max(LEASTHEIGHT, height)}px`;
        };
        fit();
        // Once more when the page has settled: web fonts and the file manager arrive late
        // and both move the bottom of the form.
        const settled = window.requestAnimationFrame(fit);
        window.addEventListener('resize', fit);
        return () => {
            window.cancelAnimationFrame(settled);
            window.removeEventListener('resize', fit);
        };
    }, []);

    // The form's own textarea is the editor; this only watches it.
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

    // Writing an image means knowing what the pictures are called, which is the one thing
    // the author cannot see from here. So the list of uploads stands beside the cursor
    // while a new image is being written and waits: typing goes on untouched, the arrow
    // keys walk the list, and only then does Enter write the file name in.
    useEffect(() => {
        const textarea = document.querySelector<HTMLTextAreaElement>('#id_content');
        if (!textarea) {
            return undefined;
        }

        const onkey = (event: KeyboardEvent) => {
            const last = menu ? menu.items.length - 1 : -1;
            if (event.key === 'ArrowDown') {
                if (!menu) {
                    // Down belongs to the cursor everywhere except in an unfinished image
                    // with something to offer, where it asks for the list.
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
                // Only a name that has been walked to is a name that was chosen; otherwise
                // Enter is what it always was.
                event.preventDefault();
                choose(menu.items[pick]);
            } else if (menu && event.key === 'Escape') {
                event.preventDefault();
                dismissed.current = true;
                setMenu(null);
            }
        };

        // Nothing here writes anything: the list follows the cursor and waits.
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

    // Walking the list with the keyboard has to keep the chosen name in sight.
    useEffect(() => {
        menuref.current
            ?.querySelector('[aria-selected="true"]')
            ?.scrollIntoView({block: 'nearest'});
    }, [pick, menu]);

    // How wide the two columns are; the divider between them moves it. Kept for the tab
    // only: Save and continue reloads the page, and coming back to a different layout
    // than the one you set is worse than not remembering it at all.
    useEffect(() => {
        document.documentElement.style.setProperty('--mudeck-split', `${split}%`);
        try {
            window.sessionStorage.setItem(SPLITKEY, String(split));
        } catch {
            // Not remembering it is not worth telling anybody about.
        }
    }, [split]);

    // A slide is drawn at its full size and scaled into the strip, so the strip has to
    // say how wide it is. A ResizeObserver catches every reason it can change: the window,
    // the divider, and a pane coming back into view.
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

    // A notification about a save has said its piece after a few seconds; leaving it there
    // means the next save changes nothing on screen and looks like it did not happen.
    // Moodle sometimes writes them in after the page has loaded, and sometimes replaces
    // the whole container, so the page is watched rather than any one element.
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
            // Only a new message starts the clock; the preview redraws constantly, and
            // restarting on every change would keep the notification on screen forever.
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

    // The file manager is Moodle's own, so it is moved here rather than rebuilt - and put
    // back where it came from if this ever unmounts, so the form still submits it.
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

        // Out of the form, its inputs would no longer be submitted and the uploads would
        // be lost on save; the form attribute keeps them part of it wherever they sit.
        // Read the id with getAttribute: this form has a field named "id", and a form
        // exposes its own controls as properties, so form.id is that field.
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

    // Writing a talk deserves the whole screen: no browser tabs, no bookmarks, no dock.
    // The button only asks; the browser decides, and can leave fullscreen without asking
    // us, so the state comes from the event rather than from the click.
    useEffect(() => {
        const watch = () => setFull(document.fullscreenElement !== null);
        watch();
        document.addEventListener('fullscreenchange', watch);
        return () => document.removeEventListener('fullscreenchange', watch);
    }, []);

    // The title is server rendered, above this component; the button belongs beside it,
    // so it is rendered through the slot the template leaves there.
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

    // What has been uploaded is read once, and again whenever the file manager changes -
    // a picture added a minute ago is offered, without a request behind every key press.
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

    // Follow the writing rather than making somebody scroll two things at once, and keep
    // the slide in the middle: at the edge of the strip it is easy to lose.
    useEffect(() => {
        stripref.current
            ?.querySelector(`[data-slide="${current}"]`)
            ?.scrollIntoView({block: 'center', behavior: 'smooth'});
    }, [current, slides]);

    /**
     * Take the writing to the slide that was clicked.
     *
     * The end of it, on the empty line before the break, which is where the next bullet
     * or paragraph goes. Finding that place in thirty slides of Markdown is the slowest
     * thing an author does; this is the way back from the picture to the text.
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
        // Moving the cursor from code fires no event, so the preview is told directly.
        redraw(textarea);
    };

    /** Drag, or arrow keys, to give one side more room. */
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
            <span className="sr-only visually-hidden">{labels.fullscreen}</span>
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
                            /* Taking the focus off the writing would close the list under the click. */
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
                            {/* Marp scopes its CSS to "div.marpit > section", so a lone slide keeps that
                                parent - and a div cannot live inside a button, which is why the button
                                lies over the slide rather than around it. */}
                            <div className="mudeck-editor-slide-box marpit" dangerouslySetInnerHTML={{__html: slide}} />
                            <button
                                type="button"
                                className="mudeck-editor-slide-jump"
                                title={labels.slide.replace('{$a}', String(index + 1))}
                                onClick={() => goToSlide(index)}
                            >
                                <span className="sr-only visually-hidden">
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
                {/* The file manager itself lives here once it has been moved. */}
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

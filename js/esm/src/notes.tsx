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
 * Speaker notes of the presentation running on another device.
 *
 * Polls the position the showing device reports and displays that slide's notes, with the
 * neighbouring slides beside it.
 *
 * @module     mod_mudeck/notes
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useRef, useState} from 'react';
import {renderParts, type PartSource, type SlideOrigin} from './render';

/** Poll interval for the showing device's position, in ms. */
const POLLEVERY = 1000;

/** Largest share of the stage width for the current slide. */
const MAINSHARE = 0.62;

/** Largest neighbour width as a share of the current slide width. */
const SIDESHARE = 0.5;

/** Neighbours narrower than this many pixels are hidden. */
const LEASTSIDE = 120;

/** Padding around the slides and gap between them, in pixels. */
const EDGE = 24;
const GAP = 16;

/** Aspect ratio of a Marp slide, 1280 by 720. */
const SHAPE = 1280 / 720;

/**
 * Format seconds as minutes and seconds, without rolling over past an hour.
 *
 * @param seconds elapsed seconds
 * @returns the formatted clock
 */
function clock(seconds: number): string {
    const minutes = Math.floor(seconds / 60);
    return `${minutes}:${String(Math.floor(seconds % 60)).padStart(2, '0')}`;
}

type Labels = {
    notes: string;
    elapsed: string;
    nonotes: string;
    waiting: string;
    stale: string;
    gone: string;
    reconnect: string;
    current: string;
    exit: string;
    fullscreen: string;
};

/** Position last reported by the showing device. */
type Position = {
    partid: number;
    parthash: string;
    slide: number;
};

type NotesProps = {
    parts: PartSource[];
    themecss?: Record<string, string>;
    pollurl: string;
    exiturl: string;
    labels: Labels;
};

/** Which message the notes panel shows. */
type Says = 'gone' | 'waiting' | 'lost' | 'nonotes' | 'notes';

/**
 * Pick the message the notes panel shows.
 *
 * @param gone the session has ended
 * @param following a device has reported a position
 * @param running that position is a slide we hold
 * @param hasnotes that slide has notes
 * @returns what to show
 */
function state(gone: boolean, following: boolean, running: boolean, hasnotes: boolean): Says {
    if (gone) {
        return 'gone';
    }
    if (!following) {
        return 'waiting';
    }
    if (!running) {
        return 'lost';
    }
    return hasnotes ? 'notes' : 'nonotes';
}

/**
 * The notes, or the reason there are none.
 *
 * @param props state, notes, labels and exit URL
 * @returns the notes panel
 */
function Said({state: said, notes, labels, exiturl}: {
    state: Says;
    notes: string;
    labels: Labels;
    exiturl: string;
}) {
    if (said === 'notes') {
        return <pre className="mudeck-notes-note">{notes}</pre>;
    }
    if (said === 'nonotes') {
        return <p className="text-muted">{labels.nonotes}</p>;
    }
    if (said === 'waiting') {
        return <p className="text-muted">{labels.waiting}</p>;
    }
    // Ended or stale: reconnecting is a deliberate choice, not automatic.
    return (
        <div className="alert alert-warning" role="status">
            <p>{said === 'gone' ? labels.gone : labels.stale}</p>
            <a href={exiturl} className="btn btn-sm btn-secondary">{labels.reconnect}</a>
        </div>
    );
}

export default function Notes({parts, themecss, pollurl, exiturl, labels}: NotesProps) {
    const stageref = useRef<HTMLDivElement>(null);
    const slideref = useRef<HTMLDivElement>(null);
    const beforeref = useRef<HTMLDivElement>(null);
    const afterref = useRef<HTMLDivElement>(null);
    const [deck, setDeck] = useState<{
        slides: string[];
        notes: string[];
        css: string;
        origins: SlideOrigin[];
    }>({
        slides: [],
        notes: [],
        css: '',
        origins: [],
    });
    const [position, setPosition] = useState<Position | null>(null);
    const [gone, setGone] = useState(false);
    // Start time in this browser's clock; every poll resets it from the server's elapsed time.
    const [began, setBegan] = useState<number | null>(null);
    const [, tick] = useState(0);

    // Render the deck once and keep each slide as markup.
    useEffect(() => {
        let cancelled = false;
        (async() => {
            const {html, css, notes, origins} = await renderParts(parts ?? [], themecss ?? {});
            if (cancelled) {
                return;
            }
            const holder = document.createElement('div');
            holder.innerHTML = html;
            setDeck({
                slides: Array.from(holder.querySelectorAll('section')).map((one) => one.outerHTML),
                notes,
                css,
                origins,
            });
        })();
        return () => {
            cancelled = true;
        };
    }, [parts, themecss]);

    // Poll the showing device; a failed poll is ignored.
    useEffect(() => {
        let stopped = false;
        const poll = async() => {
            try {
                const response = await fetch(pollurl, {headers: {Accept: 'application/json'}});
                if (!response.ok) {
                    // The session is gone; a restarted presentation makes a new one this page cannot guess.
                    if (!stopped) {
                        setGone(true);
                    }
                    return;
                }
                const reported = await response.json();
                if (!stopped && reported?.ended) {
                    setGone(true);
                    return;
                }
                if (!stopped && typeof reported?.slide === 'number') {
                    setGone(false);
                    if (typeof reported.elapsed === 'number') {
                        setBegan(Date.now() - reported.elapsed * 1000);
                    }
                    setPosition({
                        partid: Number(reported.partid) || 0,
                        parthash: String(reported.parthash ?? ''),
                        slide: reported.slide,
                    });
                }
            } catch {
                // Keep polling on network errors.
            }
        };
        poll();
        const timer = window.setInterval(poll, POLLEVERY);
        return () => {
            stopped = true;
            window.clearInterval(timer);
        };
    }, [pollurl]);

    // The clock ticks locally; the polls only correct it.
    useEffect(() => {
        const timer = window.setInterval(() => tick((was) => was + 1), POLLEVERY);
        return () => window.clearInterval(timer);
    }, []);

    // A hash mismatch means the part was edited since the show started; reloading is left to the speaker.
    const index = position
        ? deck.origins.findIndex(
            (origin) => origin.partid === position.partid && origin.offset === position.slide
        )
        : -1;
    const stale = !!position
        && index >= 0
        && !!position.parthash
        && deck.origins[index].parthash !== position.parthash;
    const running = index >= 0 && !stale && !gone;
    const slide = index + 1;
    const notes = running ? deck.notes[index] : '';
    const shown = running ? deck.slides[slide - 1] : '';
    const said = state(gone, !!position, running, !!notes);
    const sofar = began === null ? null : Math.max(0, Math.floor((Date.now() - began) / 1000));
    const before = running ? deck.slides[slide - 2] ?? '' : '';
    const after = running ? deck.slides[slide] ?? '' : '';

    // Marp slides have a fixed pixel size, so the three are scaled into the stage.
    useEffect(() => {
        const stage = stageref.current;
        if (!stage) {
            return undefined;
        }
        const fit = () => {
            const box = stage.getBoundingClientRect();
            const room = Math.max(0, box.height - EDGE);
            const main = Math.max(0, Math.min(box.width * MAINSHARE, room * SHAPE));
            const spare = (box.width - main - GAP * 2) / 2;
            const side = spare < LEASTSIDE ? 0 : Math.min(spare, main * SIDESHARE);

            const sizes: [typeof slideref, number][] = [
                [slideref, main],
                [beforeref, side],
                [afterref, side],
            ];
            sizes.forEach(([ref, width]) => {
                const node = ref.current;
                if (!node) {
                    return;
                }
                const holder = node.parentElement;
                if (holder) {
                    holder.style.display = width ? '' : 'none';
                }
                node.style.setProperty('--mudeck-width', `${width}px`);
                node.style.setProperty('--mudeck-scale', String(width / 1280));
            });
        };
        fit();
        const watcher = new ResizeObserver(fit);
        watcher.observe(stage);
        return () => watcher.disconnect();
    }, [deck, slide]);

    return (
        <div className="mudeck-notes">
            <style>{deck.css}</style>

            <div className="mudeck-notes-body">
                <div className="mudeck-notes-stage" ref={stageref}>
                    <div className="mudeck-notes-neighbour">
                        <div
                            ref={beforeref}
                            className="mudeck-notes-preview marpit"
                            aria-hidden="true"
                            dangerouslySetInnerHTML={{__html: before}}
                        />
                    </div>

                    <div className="mudeck-notes-slide">
                        <h3 className="mudeck-notes-heading visually-hidden">{labels.current}</h3>
                        {/* Marp scopes its CSS to "div.marpit > section", so a lone slide keeps that parent. */}
                        <div
                            ref={slideref}
                            className="mudeck-notes-preview marpit"
                            aria-hidden="true"
                            dangerouslySetInnerHTML={{__html: shown}}
                        />
                    </div>

                    <div className="mudeck-notes-neighbour">
                        <div
                            ref={afterref}
                            className="mudeck-notes-preview marpit"
                            aria-hidden="true"
                            dangerouslySetInnerHTML={{__html: after}}
                        />
                    </div>
                </div>

                <div className="mudeck-notes-text">
                    <h2 className="mudeck-notes-heading">{labels.notes}</h2>
                    <Said state={said} notes={notes} labels={labels} exiturl={exiturl} />
                </div>
            </div>

            <div className="mudeck-chrome mudeck-notes-chrome" data-region="mudeck-notes-chrome">
                {sofar !== null && (
                    <span className="mudeck-notes-clock" title={labels.elapsed} data-region="mudeck-notes-clock">
                        <span className="visually-hidden">{labels.elapsed}{': '}</span>
                        {clock(sofar)}
                    </span>
                )}
                <button
                    type="button"
                    className="btn btn-secondary mudeck-control"
                    onClick={() => {
                        if (document.fullscreenElement) {
                            document.exitFullscreen();
                        } else {
                            document.documentElement.requestFullscreen();
                        }
                    }}
                >
                    <i className="fa fa-expand" aria-hidden="true" />
                    <span className="visually-hidden">{labels.fullscreen}</span>
                </button>

                <a href={exiturl} className="btn btn-secondary mudeck-control">
                    <i className="fa fa-times" aria-hidden="true" />
                    <span className="visually-hidden">{labels.exit}</span>
                </a>
            </div>
        </div>
    );
}

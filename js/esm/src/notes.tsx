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
 * Reads the position the showing device reports, and shows the notes of that slide under
 * it, with the slides on either side small, so the speaker can see where they are and
 * what is coming.
 *
 * @module     mod_mudeck/notes
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useRef, useState} from 'react';
import {filterSlides} from './filters';
import {renderParts, type PartSource, type SlideOrigin} from './render';

/** How often the showing device is asked where it is. */
const POLLEVERY = 1000;

/** How much of the width the slide on screen may take. */
const MAINSHARE = 0.62;

/** How large a neighbour may be beside it. */
const SIDESHARE = 0.5;

/** A neighbour smaller than this shows nothing worth the room. */
const LEASTSIDE = 120;

/** Room around the slides, and between them. */
const EDGE = 24;
const GAP = 16;

/** What Marp lays a slide out as: 1280 by 720. */
const SHAPE = 1280 / 720;

/**
 * Minutes and seconds since the talk began.
 *
 * It keeps counting past an hour rather than rolling over: a speaker who is 63 minutes in
 * wants to be told exactly that.
 *
 * @param seconds how long it has been running
 * @returns the clock as it is read out loud
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

/** What the showing device last reported. */
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

/** What the notes half of the page has to say at the moment. */
type Says = 'gone' | 'waiting' | 'lost' | 'nonotes' | 'notes';

/**
 * Which of the five things is true.
 *
 * @param gone the session has ended
 * @param following a device has reported a position
 * @param running that position is a slide we hold
 * @param hasnotes and that slide has notes
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
 * The notes, or why there are none.
 *
 * @param props what to say, the notes themselves, the labels and the way back
 * @returns the notes half of the page
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
    // Ended, or showing a deck that is no longer the one we hold: either way the way out
    // is to connect again, which is a choice rather than something that happens by itself.
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
    // When the talk began, in this browser's own reckoning: every poll says how long it
    // has been running, which resets this, so the clock cannot drift away from the server.
    const [began, setBegan] = useState<number | null>(null);
    const [, tick] = useState(0);

    // Render the whole deck once, then keep the slides as markup we can drop in one at a time.
    useEffect(() => {
        const {html, css, notes, origins} = renderParts(parts ?? [], themecss ?? {});
        const holder = document.createElement('div');
        holder.innerHTML = html;
        setDeck({
            slides: Array.from(holder.querySelectorAll('section')).map((one) => one.outerHTML),
            notes,
            css,
            origins,
        });
    }, [parts, themecss]);

    // Follow the showing device. A failed poll is ignored: the next one is a second away.
    useEffect(() => {
        let stopped = false;
        const poll = async () => {
            try {
                const response = await fetch(pollurl, {headers: {Accept: 'application/json'}});
                if (!response.ok) {
                    // The session is gone - deleted, or ended with the browser that ran it.
                    // Restarting a presentation makes a new one, which this page cannot guess.
                    if (!stopped) {
                        setGone(true);
                    }
                    return;
                }
                const reported = await response.json();
                if (!stopped && reported?.ended) {
                    // The speaker has left the presentation; there is nothing to follow.
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
                // Keep polling - a presentation must not stop because a phone hiccuped.
            }
        };
        poll();
        const timer = window.setInterval(poll, POLLEVERY);
        return () => {
            stopped = true;
            window.clearInterval(timer);
        };
    }, [pollurl]);

    // The clock moves on its own second by second; the polls only correct it.
    useEffect(() => {
        const timer = window.setInterval(() => tick((was) => was + 1), POLLEVERY);
        return () => window.clearInterval(timer);
    }, []);

    // Find the reported slide in our own copy of the deck. The hash is what makes this
    // honest: if the part was edited since the show started, our slides are not the ones
    // on screen and the notes beside them would be a lie. Both devices are then holding
    // an old deck, so the way out is to reload both - deliberately not automatic, nothing
    // should reload itself under a speaker mid-sentence.
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
    // The three slides on the stage; the ends of the deck simply have one neighbour.
    const shown = running ? deck.slides[slide - 1] : '';
    const said = state(gone, !!position, running, !!notes);
    const sofar = began === null ? null : Math.max(0, Math.floor((Date.now() - began) / 1000));
    const before = running ? deck.slides[slide - 2] ?? '' : '';
    const after = running ? deck.slides[slide] ?? '' : '';

    // Marp lays slides out at a fixed pixel size, so the three of them are measured into
    // the room the stage has: the slide on screen as large as its height allows, and its
    // neighbours in whatever is left over on either side.
    useEffect(() => {
        const stage = stageref.current;
        if (!stage) {
            return undefined;
        }
        const fit = () => {
            const box = stage.getBoundingClientRect();
            const room = Math.max(0, box.height - EDGE);
            const main = Math.max(0, Math.min(box.width * MAINSHARE, room * SHAPE));
            // Neighbours are a luxury: they get what the middle does not need, and go
            // altogether when that is not enough to see anything in.
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

    // The slide beside the notes is built here too, so the site's filters have to be told
    // about it - otherwise a formula on the slide would show as raw TeX.
    useEffect(() => {
        if (slideref.current && running) {
            filterSlides(slideref.current);
        }
    }, [slide, running]);

    return (
        <div className="mudeck-notes">
            <style>{deck.css}</style>

            <div className="mudeck-notes-body">
                <div className="mudeck-notes-stage" ref={stageref}>
                    {/* What was, what is, what comes - the slides either side are only a reminder. */}
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

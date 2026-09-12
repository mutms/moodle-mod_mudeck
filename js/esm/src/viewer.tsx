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
 * Slide deck viewer.
 *
 * React is the mount point and owns the chrome around the deck. marp-core does the
 * rendering (render.ts) and the deck mechanics are plain DOM (presenter.ts).
 *
 * Mounted by core/react_autoinit via data-react-component="@moodle/lms/mod_mudeck/viewer".
 *
 * @module     mod_mudeck/viewer
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useCallback, useEffect, useRef, useState} from 'react';
import {filterSlides} from './filters';
import {reportReachedEnd} from './completion';
import {renderParts, type PartSource, type SlideOrigin} from './render';
import {mountPresenter, type Presenter, type PresenterState} from './presenter';

/** How long the controls are shown when the deck opens, so they are known to exist. */
const INTROFOR = 3000;

/** How long after the pointer leaves the bottom of the screen they go away again. */
const LEAVEAFTER = 1000;

/** How long a tap keeps them: a finger has no "leaves the area". */
const TOUCHFOR = 2500;

/** The share of the window height that counts as the bottom of the screen. */
const ZONE = 0.15;

type Labels = {
    previous: string;
    next: string;
    fullscreen: string;
    exit: string;
    slideof: string;
    overview: string;
    notes: string;
    nonotes: string;
};

/** Where to report the shown slide, so the presenter's other devices can follow. */
type Sync = {
    url: string;
    sesskey: string;
};

/** Where to say that the last slide was reached, when the activity counts that as completion. */
type ReachedEnd = {
    url: string;
    sesskey: string;
};

type ViewerProps = {
    parts: PartSource[];
    themecss?: Record<string, string>;
    exiturl: string;
    labels: Labels;
    sync?: Sync | null;
    reachedend?: ReachedEnd | null;
    /** Offer the speaker notes of the shown slide - preview only, never on a projector. */
    notes?: boolean;
    /** Open on this slide instead of the first one. */
    startslide?: number | null;
};

export default function Viewer({parts, themecss, exiturl, labels, sync, reachedend, notes, startslide}: ViewerProps) {
    const slidesref = useRef<HTMLDivElement>(null);
    const presenter = useRef<Presenter | null>(null);
    const origins = useRef<SlideOrigin[]>([]);
    const slidenotes = useRef<string[]>([]);
    const hidetimer = useRef<number | undefined>(undefined);
    const reported = useRef(false);
    const [state, setState] = useState<PresenterState>({current: 0, total: 0});
    const [chromevisible, setChromevisible] = useState(true);
    const [overviewopen, setOverviewopen] = useState(false);
    const [notesopen, setNotesopen] = useState(false);

    useEffect(() => {
        const node = slidesref.current;
        if (!node) {
            return undefined;
        }
        const {html, css, origins: slideorigins, notes: decknotes} = renderParts(parts ?? [], themecss ?? {});
        origins.current = slideorigins;
        slidenotes.current = decknotes;
        node.innerHTML = `<style>${css}</style>${html}`;
        presenter.current = mountPresenter(node, setState);
        if (startslide && startslide > 1) {
            presenter.current.goto(startslide);
        }
        filterSlides(node);
        return () => {
            presenter.current?.destroy();
            presenter.current = null;
        };
    }, [parts, themecss, startslide]);

    // Tell the server where we are. The part and its hash travel with the position: the
    // other device has its own copy of the deck, which may already have been edited.
    // Failures are ignored on purpose - a show must not stop because a phone dropped out.
    useEffect(() => {
        const origin = origins.current[state.current - 1];
        if (!sync || !state.total || !origin) {
            return;
        }
        fetch(sync.url, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                sesskey: sync.sesskey,
                partid: origin.partid,
                parthash: origin.parthash,
                slide: origin.offset,
                slidetitle: presenter.current?.titles()[state.current - 1] ?? '',
            }),
        }).catch(() => undefined);
    }, [sync, state]);

    // The last slide, reported once. The talk may go on, and the user may go back and
    // forth; what is being recorded is that they got there at all.
    useEffect(() => {
        if (!reachedend || reported.current || !state.total || state.current !== state.total) {
            return;
        }
        reported.current = true;
        reportReachedEnd(reachedend);
    }, [reachedend, state]);

    // Leaving says the show is over, so the session stops looking live to the speaker's
    // other devices the moment they walk away - not half an hour later. A beacon, because
    // an ordinary request made while the page is going away is not guaranteed to be sent.
    const finish = useCallback(() => {
        if (!sync) {
            return;
        }
        const body = JSON.stringify({sesskey: sync.sesskey});
        const url = `${sync.url}/end`;
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([body], {type: 'application/json'}));
            return;
        }
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body,
            keepalive: true,
        }).catch(() => undefined);
    }, [sync]);

    // Closing the tab or window is leaving as well, and every press of the start button
    // makes a session of its own, so there is nothing here worth keeping alive.
    useEffect(() => {
        window.addEventListener('pagehide', finish);
        return () => window.removeEventListener('pagehide', finish);
    }, [finish]);

    /** Show the controls, and take them away again after a while. */
    const show = useCallback((forHowLong: number | null) => {
        window.clearTimeout(hidetimer.current);
        setChromevisible(true);
        if (forHowLong !== null) {
            hidetimer.current = window.setTimeout(() => setChromevisible(false), forHowLong);
        }
    }, []);

    /** Take them away, unless something is hanging off them. */
    const hide = useCallback((after: number) => {
        window.clearTimeout(hidetimer.current);
        hidetimer.current = window.setTimeout(() => setChromevisible(false), after);
    }, []);

    // Said once, when the deck opens: here are the controls. After this they are only
    // ever summoned - never by a key, so moving through the slides changes nothing on
    // screen but the slides.
    const panelopen = overviewopen || notesopen;
    useEffect(() => {
        if (panelopen) {
            // The overview and the notes hang off the bar; it stays while they are open.
            window.clearTimeout(hidetimer.current);
            setChromevisible(true);
            return undefined;
        }
        show(INTROFOR);
        return () => window.clearTimeout(hidetimer.current);
    }, [show, panelopen]);

    // The bottom of the screen is where a hand reaches for the controls, so that is
    // where they answer. Anywhere else, the pointer is just passing over the slides.
    useEffect(() => {
        if (panelopen) {
            return undefined;
        }

        const moved = (event: PointerEvent) => {
            if (event.clientY >= window.innerHeight * (1 - ZONE)) {
                show(null);
            } else {
                hide(LEAVEAFTER);
            }
        };
        // A pointer that has gone to another screen is not coming back for the bar.
        const left = () => hide(LEAVEAFTER);
        // No hover on a touch screen: a tap near the bottom edge asks for the controls,
        // and they leave by themselves. Swipes are the presenter's, not ours.
        const tapped = (event: TouchEvent) => {
            const touch = event.touches[0];
            if (touch && touch.clientY >= window.innerHeight * (1 - ZONE)) {
                show(TOUCHFOR);
            }
        };

        document.addEventListener('pointermove', moved);
        document.addEventListener('touchstart', tapped);
        window.addEventListener('pointerleave', left);
        return () => {
            document.removeEventListener('pointermove', moved);
            document.removeEventListener('touchstart', tapped);
            window.removeEventListener('pointerleave', left);
        };
    }, [show, hide, panelopen]);

    const counter = state.total
        ? (labels.slideof || '{$a->current} / {$a->total}')
            .replace('{$a->current}', String(state.current))
            .replace('{$a->total}', String(state.total))
        : '';

    return (
        <div className={`mudeck-deck${chromevisible ? '' : ' mudeck-chrome-hidden'}`}>
            <div ref={slidesref} className="mudeck-slides" tabIndex={-1} />

            <div className="mudeck-chrome" data-region="mudeck-chrome">
                <button
                    type="button"
                    className="btn btn-secondary mudeck-control"
                    onClick={() => {
                        presenter.current?.previous();
                        presenter.current?.focus();
                    }}
                    disabled={state.current <= 1}
                >
                    <i className="fa fa-chevron-left" aria-hidden="true" />
                    <span className="visually-hidden">{labels.previous}</span>
                </button>

                <button
                    type="button"
                    className="btn btn-secondary mudeck-counter"
                    onClick={() => setOverviewopen((open) => !open)}
                    aria-expanded={overviewopen}
                    aria-controls="mudeck-overview"
                    title={labels.overview}
                >
                    <span aria-live="polite">{counter}</span>
                </button>

                <button
                    type="button"
                    className="btn btn-secondary mudeck-control"
                    onClick={() => {
                        presenter.current?.next();
                        presenter.current?.focus();
                    }}
                    disabled={state.total > 0 && state.current >= state.total}
                >
                    <i className="fa fa-chevron-right" aria-hidden="true" />
                    <span className="visually-hidden">{labels.next}</span>
                </button>

                <button
                    type="button"
                    className="btn btn-secondary mudeck-control"
                    onClick={() => {
                        presenter.current?.toggleFullscreen();
                        presenter.current?.focus();
                    }}
                >
                    <i className="fa fa-expand" aria-hidden="true" />
                    <span className="visually-hidden">{labels.fullscreen}</span>
                </button>

                {notes && (
                    <button
                        type="button"
                        className="btn btn-secondary mudeck-control"
                        onClick={() => setNotesopen((open) => !open)}
                        aria-expanded={notesopen}
                        aria-controls="mudeck-slide-notes"
                        title={labels.notes}
                    >
                        <i className="fa fa-sticky-note" aria-hidden="true" />
                        <span className="visually-hidden">{labels.notes}</span>
                    </button>
                )}

                <a href={exiturl} className="btn btn-secondary mudeck-control" onClick={finish}>
                    <i className="fa fa-times" aria-hidden="true" />
                    <span className="visually-hidden">{labels.exit}</span>
                </a>
            </div>

            {notes && notesopen && (
                <div className="mudeck-slide-notes" id="mudeck-slide-notes" data-region="mudeck-slide-notes">
                    <pre className="mudeck-slide-notes-text">
                        {slidenotes.current[state.current - 1] || labels.nonotes}
                    </pre>
                </div>
            )}

            {overviewopen && (
                <div className="mudeck-overview" id="mudeck-overview" data-region="mudeck-overview">
                    <ul className="mudeck-overview-list">
                        {(presenter.current?.titles() ?? []).map((title, i) => (
                            <li key={i}>
                                <button
                                    type="button"
                                    className={`btn btn-link mudeck-overview-item${state.current === i + 1 ? ' active' : ''}`}
                                    onClick={() => {
                                        presenter.current?.goto(i + 1);
                                        setOverviewopen(false);
                                        presenter.current?.focus();
                                    }}
                                    aria-current={state.current === i + 1}
                                >
                                    <span className="mudeck-overview-number">{i + 1}</span>
                                    <span className="mudeck-overview-title">{title}</span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

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
 * Printable copy of the presentation: one slide per page, with the speaker notes underneath.
 *
 * @module     mod_mudeck/print
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useRef, useState} from 'react';
import {renderParts, type PartSource} from './render';

type Labels = {
    print: string;
    exit: string;
    notes: string;
    nonotes: string;
};

type PrintProps = {
    parts: PartSource[];
    themecss?: Record<string, string>;
    exiturl: string;
    labels: Labels;
    /** Put the speaker notes under every slide. */
    notes?: boolean;
};

export default function Print({parts, themecss, exiturl, labels, notes}: PrintProps) {
    const pagesref = useRef<HTMLDivElement>(null);
    const [deck, setDeck] = useState<{slides: string[]; notes: string[]; css: string}>({
        slides: [],
        notes: [],
        css: '',
    });

    useEffect(() => {
        let cancelled = false;
        (async() => {
            const {html, css, notes} = await renderParts(parts ?? [], themecss ?? {});
            if (cancelled) {
                return;
            }
            const holder = document.createElement('div');
            holder.innerHTML = html;
            setDeck({
                slides: Array.from(holder.querySelectorAll('section')).map((one) => one.outerHTML),
                notes,
                css,
            });
        })();
        return () => {
            cancelled = true;
        };
    }, [parts, themecss]);

    // Marp slides have a fixed pixel size, so they are scaled to the page width.
    useEffect(() => {
        const node = pagesref.current;
        if (!node) {
            return undefined;
        }
        const fit = () => {
            const slide = node.querySelector<HTMLElement>('.mudeck-print-slide');
            if (slide) {
                node.style.setProperty('--mudeck-print-width', String(slide.clientWidth));
            }
        };
        fit();
        window.addEventListener('resize', fit);
        return () => window.removeEventListener('resize', fit);
    }, [deck]);

    return (
        <div className={notes ? 'mudeck-print' : 'mudeck-print mudeck-print-handout'}>
            <style>{deck.css}</style>
            {/* The handout's sheet is the slide itself, 1280x720 CSS pixels; printers fit it to paper. */}
            {!notes && <style>{'@page { size: 1280px 720px; margin: 0; }'}</style>}

            <div className="mudeck-print-actions">
                <button type="button" className="btn btn-primary" onClick={() => window.print()}>
                    {labels.print}
                </button>
                <a href={exiturl} className="btn btn-secondary">{labels.exit}</a>
            </div>

            <div className="mudeck-print-pages" ref={pagesref}>
                {deck.slides.map((slide, index) => (
                    <div className="mudeck-print-page" key={index}>
                        {/* Marp scopes its CSS to "div.marpit > section", so a lone slide keeps that parent. */}
                        <div
                            className="mudeck-print-slide marpit"
                            dangerouslySetInnerHTML={{__html: slide}}
                        />
                        {notes && (
                            <div className="mudeck-print-notes">
                                <h2 className="mudeck-print-notes-heading">
                                    {labels.notes} <span className="mudeck-print-number">{index + 1}</span>
                                </h2>
                                {deck.notes[index]
                                    ? <pre className="mudeck-print-note">{deck.notes[index]}</pre>
                                    : <p className="text-muted">{labels.nonotes}</p>}
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

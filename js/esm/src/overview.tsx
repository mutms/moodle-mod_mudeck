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
 * The parts a presentation is made of, as slides rather than as a table.
 *
 * Every action is still an ordinary link to the page that does it, so a broken bundle
 * costs the slide previews and nothing else - the server renders the same list behind
 * this component until it mounts.
 *
 * @module     mod_mudeck/overview
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useRef, useState} from 'react';
import {confirmed} from './confirm';
import {renderPart, type PartSource} from './render';

/** Where the open part is remembered, so a full page reload does not collapse everything. */
const OPENKEY = 'mudeck-overview-open';

type Part = PartSource & {
    name: string;
    slides: number;
    editurl: string;
    previewurl: string;
    exporturl: string;
    deleteurl: string;
    upurl: string;
    downurl: string;
};

type Labels = {
    edit: string;
    deleteconfirm: string;
    deletetitle: string;
    move: string;
    moveto: string;
    moved: string;
    export: string;
    delete: string;
    empty: string;
    show: string;
    noparts: string;
};

type OverviewProps = {
    parts: Part[];
    themecss?: Record<string, string>;
    labels: Labels;
    /** Where a part reports its new place, and the key that lets it. */
    moveurl: string;
    sesskey: string;
};

/**
 * Choose where a part goes, for anybody not using a mouse.
 *
 * The move waits for the button: arrowing through a list of positions must not move
 * anything until the choice is made.
 *
 * @param props places to choose from, where the part is now, and what to do with the answer
 * @returns the position chooser
 */
function PositionChooser({id, label, confirm, count, current, onPick, onCancel}: {
    id: string;
    label: string;
    confirm: string;
    count: number;
    current: number;
    onPick: (position: number) => void;
    onCancel: () => void;
}) {
    const ref = useRef<HTMLSelectElement>(null);

    useEffect(() => {
        ref.current?.focus();
    }, []);

    return (
        <>
            <label className="visually-hidden" htmlFor={id}>{label}</label>
            <select
                ref={ref}
                id={id}
                className="form-select form-select-sm mudeck-part-position"
                defaultValue={current}
                onKeyDown={(event) => {
                    if (event.key === 'Escape') {
                        onCancel();
                    }
                }}
            >
                {Array.from({length: count}, (_value, place) => (
                    <option key={place} value={place + 1}>{place + 1}</option>
                ))}
            </select>
            <button
                type="button"
                className="btn btn-sm btn-primary mudeck-part-position-go"
                onClick={() => onPick(Number(ref.current?.value ?? current))}
            >
                {confirm}
            </button>
        </>
    );
}

/** One part rendered to slides, kept until the page is left. */
type Rendered = {
    slides: string[];
    css: string;
};

export default function Overview({parts, themecss, labels, moveurl, sesskey}: OverviewProps) {
    const thumbsref = useRef<HTMLOListElement>(null);
    const [open, setOpen] = useState<number | null>(null);
    const [rendered, setRendered] = useState<Record<number, Rendered>>({});
    const [order, setOrder] = useState<Part[]>(parts);
    const [dragging, setDragging] = useState<number | null>(null);
    const [choosing, setChoosing] = useState<number | null>(null);
    const [announcement, setAnnouncement] = useState('');

    useEffect(() => setOrder(parts), [parts]);

    // Come back to the part that was open before the page reloaded.
    useEffect(() => {
        let remembered = null;
        try {
            remembered = window.sessionStorage.getItem(OPENKEY);
        } catch {
            // Private windows and blocked storage simply start collapsed.
        }
        const id = remembered ? Number(remembered) : null;
        if (id && parts.some((part) => part.id === id)) {
            setOpen(id);
        } else if (parts.length === 1) {
            // One part is the usual case, and a single collapsed row helps nobody.
            setOpen(parts[0].id);
        }
    }, [parts]);

    // Slides are only built for the part on screen, so a long deck costs nothing until asked.
    useEffect(() => {
        if (open === null || rendered[open]) {
            return;
        }
        const part = parts.find((one) => one.id === open);
        if (!part) {
            return;
        }
        const {html, css} = renderPart(part.markdown, part.theme, themecss ?? {});
        const holder = document.createElement('div');
        holder.innerHTML = html;
        setRendered((all) => ({
            ...all,
            [open]: {
                slides: Array.from(holder.querySelectorAll('section')).map((one) => one.outerHTML),
                css,
            },
        }));
    }, [open, parts, themecss, rendered]);

    // A thumbnail is a whole slide scaled into one grid column, so it has to be measured.
    useEffect(() => {
        const list = thumbsref.current;
        if (!list) {
            return undefined;
        }
        const fit = () => {
            const first = list.querySelector('.mudeck-thumb-link');
            if (first) {
                list.style.setProperty('--mudeck-thumb-width', String(first.clientWidth));
            }
        };
        fit();
        window.addEventListener('resize', fit);
        return () => window.removeEventListener('resize', fit);
    }, [open, rendered]);

    /**
     * Delete a part, once the person has said so out loud.
     *
     * The part leaves the page first; if the server refuses, it comes back, because
     * pretending something is gone when it is not would be the worse lie.
     */
    const remove = async (part: Part) => {
        const question = labels.deleteconfirm.replace('{$a}', part.name);
        if (!await confirmed(labels.deletetitle, question, labels.delete)) {
            return;
        }
        const previous = order;
        setOrder(order.filter((one) => one.id !== part.id));

        try {
            const response = await fetch(`${moveurl}${part.id}/delete`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({sesskey}),
            });
            if (!response.ok) {
                setOrder(previous);
            }
        } catch {
            setOrder(previous);
        }
    };

    /**
     * Put a part at a given place, on the page and then on the server.
     *
     * The list is moved first so the page keeps up with the mouse; a refusal from the
     * server puts it back, because the order on the server is the one that counts.
     */
    const moveTo = (id: number, position: number) => {
        const from = order.findIndex((part) => part.id === id);
        const to = position - 1;
        if (from < 0 || to < 0 || to >= order.length || to === from) {
            return;
        }
        const previous = order;
        const moved = [...order];
        moved.splice(to, 0, ...moved.splice(from, 1));
        setOrder(moved);
        setAnnouncement(labels.moved.replace('{$a}', String(position)));

        fetch(`${moveurl}${id}/position`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({sesskey, position}),
        })
            .then((response) => {
                if (!response.ok) {
                    // The order on the server is the one that counts.
                    setOrder(previous);
                }
            })
            .catch(() => setOrder(previous));
    };

    const toggle = (id: number) => {
        const next = open === id ? null : id;
        setOpen(next);
        try {
            if (next === null) {
                window.sessionStorage.removeItem(OPENKEY);
            } else {
                window.sessionStorage.setItem(OPENKEY, String(next));
            }
        } catch {
            // Not being able to remember it is not worth telling anybody about.
        }
    };

    if (!order.length) {
        // The server says the same thing until this mounts; it has to keep saying it
        // afterwards, or a presentation with no parts looks like a page still loading.
        return (
            <div className="alert alert-info" role="status">{labels.noparts}</div>
        );
    }

    return (
        <div className="mudeck-parts">
            <div className="visually-hidden" aria-live="polite">{announcement}</div>
            {order.map((part, index) => (
                <section
                    className={`mudeck-part${dragging === part.id ? ' mudeck-part-dragging' : ''}`}
                    key={part.id}
                    draggable
                    onDragStart={() => setDragging(part.id)}
                    onDragEnd={() => setDragging(null)}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={(event) => {
                        event.preventDefault();
                        if (dragging && dragging !== part.id) {
                            moveTo(dragging, index + 1);
                        }
                        setDragging(null);
                    }}
                >
                    <div className="mudeck-part-header">
                        <button
                            type="button"
                            className="mudeck-part-toggle"
                            onClick={() => toggle(part.id)}
                            aria-expanded={open === part.id}
                            aria-controls={`mudeck-part-${part.id}`}
                        >
                            <i
                                className={`fa fa-chevron-${open === part.id ? 'down' : 'right'}`}
                                aria-hidden="true"
                            />
                            <span className="mudeck-part-name">{part.name}</span>
                        </button>

                        <div className="mudeck-part-actions">
                            <a href={part.editurl} className="btn btn-sm btn-primary">{labels.edit}</a>
                            {order.length > 1 && choosing !== part.id && (
                                <button
                                    type="button"
                                    className="btn btn-sm btn-secondary mudeck-part-move"
                                    onClick={() => setChoosing(part.id)}
                                    title={labels.move}
                                >
                                    <i className="fa fa-arrows-up-down-left-right" aria-hidden="true" />
                                    <span className="visually-hidden">{labels.move}</span>
                                </button>
                            )}
                            {choosing === part.id && (
                                <PositionChooser
                                    id={`mudeck-move-${part.id}`}
                                    label={labels.moveto}
                                    confirm={labels.move}
                                    count={order.length}
                                    current={index + 1}
                                    onPick={(position) => {
                                        moveTo(part.id, position);
                                        setChoosing(null);
                                    }}
                                    onCancel={() => setChoosing(null)}
                                />
                            )}
                            <a href={part.exporturl} className="btn btn-sm btn-secondary" title={labels.export}>
                                <i className="fa fa-download" aria-hidden="true" />
                                <span className="visually-hidden">{labels.export}</span>
                            </a>
                            <button
                                type="button"
                                className="btn btn-sm btn-outline-danger"
                                onClick={() => remove(part)}
                                title={labels.delete}
                            >
                                <i className="fa fa-trash" aria-hidden="true" />
                                <span className="visually-hidden">{labels.delete}</span>
                            </button>
                        </div>
                    </div>

                    {open === part.id && (
                        <div className="mudeck-part-body" id={`mudeck-part-${part.id}`}>
                            {!part.slides && <p className="text-muted">{labels.empty}</p>}
                            {!!part.slides && rendered[part.id] && (
                                <>
                                    <style>{rendered[part.id].css}</style>
                                    <ol className="mudeck-thumbs" ref={thumbsref}>
                                        {rendered[part.id].slides.map((slide, index) => (
                                            <li key={index} className="mudeck-thumb">
                                                <a
                                                    href={`${part.previewurl}&slide=${index + 1}`}
                                                    className="mudeck-thumb-link"
                                                    title={`${labels.show} ${index + 1}`}
                                                >
                                                    {/* Marp scopes its CSS to "div.marpit > section",
                                                        so a lone slide keeps that parent. */}
                                                    <div
                                                        className="mudeck-thumb-slide marpit"
                                                        aria-hidden="true"
                                                        dangerouslySetInnerHTML={{__html: slide}}
                                                    />
                                                    <span className="mudeck-thumb-number">{index + 1}</span>
                                                </a>
                                            </li>
                                        ))}
                                    </ol>
                                </>
                            )}
                        </div>
                    )}
                </section>
            ))}
        </div>
    );
}

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
 * The first slide on the welcome page, drawn inside the start card.
 *
 * @module     mod_mudeck/welcome
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useRef, useState} from 'react';
import {renderPart} from './render';

type WelcomeProps = {
    markdown: string;
    theme: string;
    themecss?: Record<string, string>;
};

export default function Welcome({markdown, theme, themecss}: WelcomeProps) {
    const boxref = useRef<HTMLDivElement>(null);
    const [slide, setSlide] = useState<{html: string; css: string} | null>(null);

    useEffect(() => {
        let cancelled = false;
        (async() => {
            const {html, css} = await renderPart(markdown, theme, themecss ?? {});
            if (cancelled) {
                return;
            }
            const holder = document.createElement('div');
            holder.innerHTML = html;
            const first = holder.querySelector('section');
            setSlide(first ? {html: first.outerHTML, css} : null);
        })();
        return () => {
            cancelled = true;
        };
    }, [markdown, theme, themecss]);

    // Marp draws at 1280x720; the slide is scaled to whatever width the card has.
    useEffect(() => {
        const box = boxref.current;
        if (!box) {
            return undefined;
        }
        const fit = () => box.style.setProperty('--mudeck-poster-width', String(box.clientWidth));
        fit();
        const watcher = new ResizeObserver(fit);
        watcher.observe(box);
        return () => watcher.disconnect();
    }, [slide]);

    return (
        <div className="mudeck-poster-box" ref={boxref} aria-hidden="true">
            {slide && (
                <>
                    <style>{slide.css}</style>
                    <div className="mudeck-poster-slide marpit" dangerouslySetInnerHTML={{__html: slide.html}} />
                </>
            )}
        </div>
    );
}

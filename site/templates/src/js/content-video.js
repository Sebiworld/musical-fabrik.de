/* jshint -W024 */
import { createElementFromHTML, addClass } from './classes/helpers.js';

(async () => {
    const elements = document.querySelectorAll('.video-element');
    if (elements.length > 0) {
        for (const element of elements) {
            if (typeof element !== 'object' || !(element instanceof Element)) {
                continue;
            }

            if (element.hasAttribute('data-youtube-id')) {
                element.addEventListener("click", replaceWithYoutubePlayer.bind(element), {once: true});
            }
        }
    }
})();

function replaceWithYoutubePlayer(event) {
    event.preventDefault();
    const element = this;

    if (typeof element !== 'object' || !(element instanceof Element)) {
        return false;
    }

    const youtubeId = element.getAttribute('data-youtube-id');
    if (typeof youtubeId === 'string' && youtubeId.length < 1) {
        return false;
    }

    const newEl = createElementFromHTML(/*html*/`
    <iframe 
        class="ar-content"
        src="https://www.youtube-nocookie.com/embed/${youtubeId}?controls=0&rel=0&autoplay=1&color=white&showinfo=0&modestbranding=1&iv_load_policy=0" 
        frameborder="0" 
        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" 
        allowfullscreen="allowfullscreen">
    </iframe>
    `);

    // replace el with newEL
    addClass(element, 'video-loaded');

    const placeholder = element.querySelector('.placeholder');
    if(typeof placeholder === 'object' && placeholder instanceof Element){
        // Replace placeholder with youtube-iframe
        placeholder.parentNode.replaceChild(newEl, placeholder);
    }else{
        // No placeholder image found, just append the youtube-iframe
        element.appendChild(newEl);
    }
}
/* jshint -W024 */
import { ready } from './classes/hilfsfunktionen.js';

(async () => {
    const elemente = document.querySelectorAll('video-player');
    if (elemente.length > 0) {

        const { default: Vue } = await import(/* webpackChunkName: "vue" */ "vue");

        const VideoPlayerLoad = await import("./vue-components/VideoPlayer.vue");
        const VideoPlayer = VideoPlayerLoad.default;

        ready(function () {
            for (let index in elemente) {
                const element = elemente[index];
                if (typeof element !== 'object' || !(element instanceof Element)) {
                    continue;
                }

                new Vue({
                    el: element,
                    components: { VideoPlayer }
                });
            }
        });
    }
})();
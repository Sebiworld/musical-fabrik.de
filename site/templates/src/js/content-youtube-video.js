/* jshint -W024 */
import { ready } from './classes/helpers.js';

(async () => {
    const elements = document.querySelectorAll('video-player');
    if (elements.length > 0) {

        const { default: Vue } = await import(/* webpackChunkName: "vue" */ "vue");

        const VideoPlayerLoad = await import("./vue-components/VideoPlayer.vue");
        const VideoPlayer = VideoPlayerLoad.default;

        ready(function () {
            for (let index in elements) {
                const element = elements[index];
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
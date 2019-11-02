/* jshint -W024 */
import { ready } from "./classes/helpers";

(async () => {
    const elements = document.querySelectorAll('.section_custom_hero .vue-comp');
    if (elements.length > 0) {

        const { default: Vue } = await import(/* webpackChunkName: "vue" */ "vue");

        const StarHeroLoad = await import(/* webpackChunkName: "vue-isfahan-hero" */ "./vue-components/StarHero/StarHero.vue");
        const StarHero = StarHeroLoad.default;

        ready(function () {
            for (let index in elements) {
                const element = elements[index];
                if (typeof element !== 'object' || !(element instanceof Element)) {
                    continue;
                }

                new Vue({
                    el: element,
                    render: h => h(StarHero)
                });
            }
        });
    }
})();

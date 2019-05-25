import { ready } from "./classes/hilfsfunktionen";

(async () => {
    const elemente = document.querySelectorAll('.sektion-custom-hero .vue-comp');
    if (elemente.length > 0) {

        const vueLoad = await import(/* webpackChunkName: "vuejs" */ "vue");
        const Vue = vueLoad.default;

        const StarHeroLoad = await import(/* webpackChunkName: "vue-isfahan-hero" */ "./vue-components/StarHero/StarHero.vue");
        const StarHero = StarHeroLoad.default;

        ready(function () {
            for (let index in elemente) {
                const element = elemente[index];
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

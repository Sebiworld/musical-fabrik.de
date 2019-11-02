<template>
  <div class="isfahan-hero">
    <div class="hero-bild seitenverhaeltnis sv-16-9">
      <div class="sv-inhalt">
        <himmel ref="himmel"></himmel>
        <img src="./Sterne.svg" class="image-part">
        <leuchtende-sterne ref="sterne"></leuchtende-sterne>
        <sternschnuppen ref="sternschnuppen"></sternschnuppen>
        <img src="./Himmelsleuchten.svg" class="image-part">
        <img src="./Stadt.svg" class="image-part">
        <img src="./Wüste hinten.svg" class="image-part">
        <img src="./Wüste Mitte.svg" class="image-part">
        <img src="./Karawane.svg" class="image-part">
        <img src="./Wüste Vorne.svg" class="image-part">
        <div class="events">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            xmlns:xlink="http://www.w3.org/1999/xlink"
            width="100%"
            height="100%"
            style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421"
            version="1.1"
            viewBox="0 0 5120 2883"
            xml:space="preserve"
          >
            <g id="kamelpopo">
              <rect
                width="130"
                height="300"
                x="4550"
                y="1250"
                style="fill:transparent"
                @click="arschklick"
              ></rect>
            </g>

            <g id="stadt">
              <rect
                width="1400"
                height="600"
                x="170"
                y="1450"
                style="fill:transparent"
                @click="stadtklick"
              ></rect>
            </g>
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Himmel from "./Himmel.vue";
import LeuchtendeSterne from "./LeuchtendeSterne.vue";
import Sternschnuppen from "./Sternschnuppen.vue";
import GoldInfo from "./GoldInfo.vue";

export default {
  components: { Himmel, LeuchtendeSterne, Sternschnuppen, GoldInfo },
  data() {
    return {};
  },
  computed: {},
  methods: {
    stadtklick() {
      this.$refs.himmel.toggleRainbow();
    },
    arschklick() {
      this.$refs.sternschnuppen.shootStar();
    }
  }
};
</script>

<style scoped lang="scss">
@import "./globals/index";

.isfahan-hero {
  height: 100%;
  width: 100%;
}
.hero-bild {
  overflow: hidden;
}

.image-part {
  display: block;
  position: absolute;
  left: 0;
  bottom: 0;
  width: auto;
  height: auto;
}
.events {
  z-index: 0;
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
}
</style>
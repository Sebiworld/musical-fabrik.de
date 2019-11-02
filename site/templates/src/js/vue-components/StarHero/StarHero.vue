<template>
  <div class="isfahan-hero">
    <div class="hero-bild aspect-ratio ar-2-1">
      <div class="ar-content">
        <himmel ref="himmel"></himmel>
        <img src="./Sterne.svg" class="image-part">
        <leuchtende-sterne ref="sterne"></leuchtende-sterne>
        <sternschnuppen ref="sternschnuppen"></sternschnuppen>
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
  top: 0;
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
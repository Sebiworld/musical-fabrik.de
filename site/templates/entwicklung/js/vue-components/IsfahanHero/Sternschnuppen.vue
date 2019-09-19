<template>
  <div class="sternschnuppen">
    <svg
      width="100%"
      height="100%"
      viewBox="0 0 5120 2880"
      version="1.1"
      xmlns="http://www.w3.org/2000/svg"
      xmlns:xlink="http://www.w3.org/1999/xlink"
      xml:space="preserve"
      style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"
    >
      <sternschnuppe
        v-for="(sternschnuppe, index) in sternschnuppen"
        :key="index"
        :x="sternschnuppe.x"
        :y="sternschnuppe.y"
        :angle="sternschnuppe.angle"
        :length="sternschnuppe.length"
      ></sternschnuppe>
    </svg>
  </div>
</template>

<script>
import { random } from "lodash-es";
import Sternschnuppe from "./Sternschnuppe.vue";
import { setIntervalAsync } from "../../classes/hilfsfunktionen";

const possibleAngles = [200, 220, 240, 300, 320, 340];
// const possibleAngles = [200, 220, 240, 260, 280, 300, 320, 340];

export default {
  data() {
    return {
      frequenzMin: 100,
      frequenzMax: 5000,
      sternschnuppen: []
    };
  },
  components: { Sternschnuppe },
  computed: {},
  methods: {
    /**
     * Animiert eine Sternschnuppe
     * :x="2062" :y="1245" :angle="290" :length="500"
     */
    shootStar() {
      const obj = this;

      return new Promise((resolve, reject) => {
        const x = random(0, 5120);
        const y = random(1000, 1300);
        const angle = possibleAngles[random(possibleAngles.length - 1)];
        const sternschnuppe = {
          x: x,
          y: y,
          angle: angle
        };

        this.sternschnuppen.push(sternschnuppe);

        // Nach einer Sekunde kann die Sternschnuppe wieder gelöscht werden:
        setTimeout(function() {
          const index = obj.sternschnuppen.indexOf(sternschnuppe);
          if (index > -1) {
            obj.sternschnuppen.splice(index, 1);
          }
          resolve();
        }, 1000);
      });
    }
  },
  mounted() {
    const obj = this;

    setTimeout(function() {
      setIntervalAsync(obj.shootStar, obj.frequenzMin, obj.frequenzMax);
    }, 1000);
  }
};
</script>

<style scoped lang="scss">
@import "./globals/index";

.sternschnuppen {
  z-index: 0;
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
}
</style>
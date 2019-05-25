<template>
  <canvas class="background-canvas himmel"></canvas>
</template>

<script>
import Granim from "granim";

export default {
  data() {
    return {
      granimInstance: false
    };
  },
  methods: {
    toggleRainbow() {
      if (this.granimInstance instanceof Granim) {
        if (this.granimInstance.activeState !== "rainbow-state") {
          this.granimInstance.changeState("rainbow-state");
        } else {
          this.granimInstance.changeState("default-state");
        }
      }
    }
  },
  mounted() {
    this.granimInstance = new Granim({
      element: ".isfahan-hero .background-canvas",
      name: "granim",
      direction: "custom",
      customDirection: {
        x0: "50%",
        y0: "0px",
        x1: "58%",
        y1: "100%"
      },
      opacity: [1, 1],
      states: {
        "default-state": {
          gradients: [
            [
              { color: "#08030E", pos: 0 },
              { color: "#2C182C", pos: 0.33 },
              { color: "#69454B", pos: 1 }
            ],
            [
              { color: "#08030E", pos: 0 },
              { color: "#2D1918", pos: 0.33 },
              { color: "#8B2B2B", pos: 1 }
            ],
            [
              { color: "#08030E", pos: 0 },
              { color: "#2C182C", pos: 0.33 },
              { color: "#69454B", pos: 1 }
            ],
            [
              { color: "#08030E", pos: 0 },
              { color: "#182D22", pos: 0.33 },
              { color: "#636945", pos: 1 }
            ]
            // [
            //   { color: "#08030E", pos: 0 },
            //   { color: "#2C182C", pos: 0.33 },
            //   { color: "#69454B", pos: 1 }
            // ],
            // [
            //   { color: "#08030E", pos: 0 },
            //   { color: "#2D1918", pos: 0.33 },
            //   { color: "#CA2C1D", pos: 1 }
            // ],
            // [
            //   { color: "#08030E", pos: 0 },
            //   { color: "#2D1918", pos: 0.33 },
            //   { color: "#CAA91D", pos: 1 }
            // ],
            // [
            //   { color: "#08030E", pos: 0 },
            //   { color: "#2D1918", pos: 0.33 },
            //   { color: "#46CA1D", pos: 1 }
            // ],
            // [
            //   { color: "#08030E", pos: 0 },
            //   { color: "#2D1918", pos: 0.33 },
            //   { color: "#1DCAA8", pos: 1 }
            // ],
            // [
            //   { color: "#08030E", pos: 0 },
            //   { color: "#2D1918", pos: 0.33 },
            //   { color: "#1D27CA", pos: 1 }
            // ],
            // [
            //   { color: "#08030E", pos: 0 },
            //   { color: "#2D1918", pos: 0.33 },
            //   { color: "#841DCA", pos: 1 }
            // ],
            // [
            //   { color: "#08030E", pos: 0 },
            //   { color: "#2D1918", pos: 0.33 },
            //   { color: "#CA1DA1", pos: 1 }
            // ]
          ],
          transitionSpeed: 10000
        }
      }
    });
  }
};
</script>

<style scoped lang="scss">
@import "./globals/index";

.himmel {
  display: block;
  position: absolute;
  left: 0;
  bottom: 0;
  right: 0;
  top: 0;
  width: 100%;
  height: 100%;
}
</style>
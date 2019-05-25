<template>
  <g class="sternschnuppe" :class="'a-' + angleRounded">
    <circle :cx="x" :cy="y" :r="size / 2" :style="{fill:color}"></circle>
    <line
      :x1="x"
      :y1="y"
      :x2="x2"
      :y2="y2"
      :stroke="color"
      :stroke-width="size / 2.5"
      stroke-linecap="round"
    />
  </g>
</template>

<script>
export default {
  props: {
    x: {
      type: Number,
      required: true
    },
    y: {
      type: Number,
      required: true
    },
    angle: {
      type: Number,
      required: true
    },
    size: {
      type: Number,
      default: 12
    },
    color: {
      type: String,
      default: "#fff"
    }
  },
  computed: {
    angleRounded() {
      return Math.round(this.angle * 20) / 20;
    },
    x2() {
      return (
        Math.cos((this.angleRounded * Math.PI) / 180) * (this.size * 15) +
        this.x
      );
    },
    y2() {
      return (
        Math.sin((this.angleRounded * Math.PI) / 180) * (this.size * 15) +
        this.y
      );
    }
  }
};
</script>

<style scoped lang="scss">
@import "./globals/index";

@keyframes shooting-star-200 {
  0% {
    opacity: 1;
    transform: translate(-1409px, -513px);
  }
  25% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(0, 0);
  }
}

@keyframes shooting-star-220 {
  0% {
    opacity: 1;
    transform: translate(-1149px, -964px);
  }
  25% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(0, 0);
  }
}

@keyframes shooting-star-240 {
  0% {
    opacity: 1;
    transform: translate(-750px, -1299px);
  }
  25% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(0, 0);
  }
}

@keyframes shooting-star-260 {
  0% {
    opacity: 1;
    transform: translate(-260px, -1477px);
  }
  25% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(0, 0);
  }
}

@keyframes shooting-star-280 {
  0% {
    opacity: 1;
    transform: translate(260px, -1477px);
  }
  25% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(0, 0);
  }
}

@keyframes shooting-star-300 {
  0% {
    opacity: 1;
    transform: translate(750px, -1299px);
  }
  25% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(0, 0);
  }
}

@keyframes shooting-star-320 {
  0% {
    opacity: 1;
    transform: translate(1149px, -964px);
  }
  25% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(0, 0);
  }
}

@keyframes shooting-star-340 {
  0% {
    opacity: 0;
    transform: translate(1409px, -513px);
  }
  25% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(0, 0);
  }
}

.sternschnuppe {
  opacity: 0;
  animation-duration: 0.8s;
  animation-iteration-count: 1;
  animation-timing-function: ease-out;
  transform-box: fill-box;

  &.a-200 {
    animation-name: shooting-star-200;
  }
  &.a-220 {
    animation-name: shooting-star-220;
  }
  &.a-240 {
    animation-name: shooting-star-240;
  }
  &.a-260 {
    animation-name: shooting-star-260;
  }
  &.a-280 {
    animation-name: shooting-star-280;
  }
  &.a-300 {
    animation-name: shooting-star-300;
  }
  &.a-320 {
    animation-name: shooting-star-320;
  }
  &.a-340 {
    animation-name: shooting-star-340;
  }
}
</style>
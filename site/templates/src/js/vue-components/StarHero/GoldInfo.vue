<template>
  <div class="gold-info">
    <svg
      width="100%"
      height="100%"
      viewBox="0 0 5120 1180"
      version="1.1"
      xmlns="http://www.w3.org/2000/svg"
      xmlns:xlink="http://www.w3.org/1999/xlink"
      xml:space="preserve"
      xmlns:serif="http://www.serif.com/"
      style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"
    >
      <g id="Info">
        <path
          d="M5120,1.648l-5120,577.706l0,617.207l5120,-9.756l0,-1185.16Z"
          style="fill:url(#_Linear1);"
        ></path>
        <path d="M5120,1.648l-5120,577.706l0,617.207l5120,-9.756l0,-1185.16Z" fill="url(#muster)"></path>
      </g>
      <defs>
        <linearGradient
          id="_Linear1"
          x1="0"
          x2="1"
          y1="0"
          y2="0"
          gradientTransform="matrix(-5687.98,-2051.91,1751.22,-4854.46,5487.65,790.048)"
          gradientUnits="userSpaceOnUse"
        >
          <stop offset="0" style="stop-color:#220700;stop-opacity:1"></stop>
          <stop offset="0.19" style="stop-color:#5d3300;stop-opacity:1"></stop>
          <stop offset="0.62" style="stop-color:#8f5900;stop-opacity:1"></stop>
          <stop offset="1" style="stop-color:#cda039;stop-opacity:1"></stop>

          <!-- <stop offset="0" style="stop-color:#cda039;stop-opacity:1"></stop>
            <stop offset="0.38" style="stop-color:#8f5900;stop-opacity:1"></stop>
            <stop offset="0.81" style="stop-color:#5d3300;stop-opacity:1"></stop>
          <stop offset="1" style="stop-color:#220700;stop-opacity:1"></stop>-->
        </linearGradient>
        <pattern id="muster" patternUnits="userSpaceOnUse" width="500" height="671">
          <image
            xlink:href="/site/templates/assets/static_img/muster.png"
            x="0"
            y="0"
            width="500"
            height="671"
          ></image>
        </pattern>
      </defs>
    </svg>
    <div class="medicus-logo">
      <a href="/projekte/der-medicus/">
        <img src="./medicus_logo_weiss.png">
      </a>
    </div>
    <div class="medicus-teaser">
      <div class="info-text">Aufführungen vom 19. bis 29. März 2020</div>
      <a class="btn background medicus" href="/projekte/der-medicus/">zur Projektseite</a>
    </div>
  </div>
</template>

<script>
export default {
  computed: {},
  methods: {},
  mounted() {}
};
</script>

<style scoped lang="scss">
@import "./globals/index";

.gold-info {
  z-index: 10;
  display: block;
  position: relative;
  width: 100%;
  height: auto;
  max-height: 255px;
  margin-top: -14%;

  #InfoOverlay {
    fill: url(./muster.png);
  }
}

.medicus-logo {
  position: absolute;
  right: 0;
  bottom: 0;
  top: 15%;
  width: 50%;
  display: flex;
  flex-direction: row;
  align-items: flex-end;
  justify-content: flex-end;
  padding: 10px 12px 10px 24px;

  img {
    display: block;
    max-width: 100%;
  }
}

.medicus-teaser {
  position: absolute;
  left: 0;
  bottom: 0;
  top: 15%;
  width: 50%;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding: 10px 24px 10px 12px;
  text-align: left;
  justify-content: flex-end;

  .info-text {
    padding: 5px 10px;
    color: #fff;

    @include media-breakpoint-down("sm") {
      background: #fff;
      color: $body-color;
      @include font-size(0.8rem);
    }
  }
}
</style>
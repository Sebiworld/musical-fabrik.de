<template>
  <video ref="videoPlayer" class="video-js">
    <slot></slot>
  </video>
</template>

<script>
// import videojs from "video.js";

export default {
  name: "VideoPlayer",
  props: {
    options: {
      type: Object,
      default() {
        return {
          preload: "none",
          autoplay: false,
          controls: true,
          fluid: true,
          aspectRatio: '16:9'
        };
      }
    }
  },
  data() {
    return {
      player: null
    };
  },
  mounted() {
    (async () => {
      const videojsLoad = await import("video.js");
      const videojs = videojsLoad.default;

      const videojsYTLoad = await import("videojs-youtube");
      const videojsYT = videojsYTLoad.default;

      const youtubeID = this.$el.getAttribute("data-youtube-id");
      if (typeof youtubeID === "string" && youtubeID.length > 0) {
        this.options.techOrder = ["youtube"];
        this.options.sources = [
          {
            type: "video/youtube",
            src: "https://www.youtube.com/watch?v=" + youtubeID
          }
        ];
        this.options.youtube = { ytControls: 0, iv_load_policy: 3, autoplay: 0, ytOrigin: window.location.origin, rel: 0, enablePrivacyEnhancedMode: false };
      } else {
      }

      this.player = videojs(
        this.$refs.videoPlayer,
        this.options
      );
    })();
  },
  beforeDestroy() {
    if (this.player) {
      this.player.dispose();
    }
  }
};
</script>
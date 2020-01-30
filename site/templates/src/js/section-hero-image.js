import Granim from "granim";

const granimInstance = new Granim({
    element: ".section_hero_image .background-canvas",
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
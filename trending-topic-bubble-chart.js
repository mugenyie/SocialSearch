$(document).ready(function () {
  var bubbleChart = new d3.svg.BubbleChart({
    supportResponsive: true,
    //container: => use @default
    size: 600,
    //viewBoxSize: => use @default
    innerRadius: 600 / 3.5,
    //outerRadius: => use @default
    radiusMin: 50,
    //radiusMax: use @default
    //intersectDelta: use @default
    //intersectInc: use @default
    //circleColor: use @default
    data: {
      items: [
        {text: "MTN", count: "236", url: "./index.php?a=search&q=MTN"},
        {text: "Internship", count: "200", url: "./index.php?a=search&q=Internship"},
        {text: "Stella Nyanzi", count: "170", url: "./index.php?a=search&q=Stella Nyanzi"},
        {text: "Rugby Cranes", count: "123", url: "./index.php?a=search&q=Rugby Cranes"},
        {text: "@airtel", count: "12", url: "./index.php?a=search&q=@airtel"},
        {text: "Python", count: "170", url: "./index.php?a=search&q=Python"},
        {text: "Ssebaana Kizito", count: "382", url: "./index.php?a=search&q=Ssebaana Kizito"},
        {text: "Kyambogo", count: "12", url: "./index.php?a=search&q=Kyambogo"},
        {text: "MUBS", count: "10", url: "./index.php?a=search&q=MUBS"},
        {text: "kampala", count: "12", url: "./index.php?a=search&q=kampala"},
        {text: "MUK", count: "300", url: "./index.php?a=search&q=MUK"},
        {text: "High school", count: "170", url: "./index.php?a=search&q=High School"},
      ],
      eval: function (item) {return item.count;},
      classed: function (item) {return item.text.split(" ").join("");}
    },
    plugins: [
      {
        name: "central-click",
        options: {
          text: "(See more detail)",
          style: {
            "font-size": "12px",
            "font-style": "italic",
            "font-family": "Source Sans Pro, sans-serif",
            //"font-weight": "700",
            "text-anchor": "middle",
            "fill": "white"
          },
          attr: {dy: "65px"},
          centralClick: function(item) {
            window.open(item.url, '_self');
          }
        }
      },
      {
        name: "lines",
        options: {
          format: [
            {// Line #0
              textField: "count",
              classed: {count: true},
              style: {
                "font-size": "28px",
                "font-family": "Source Sans Pro, sans-serif",
                "text-anchor": "middle",
                fill: "white"
              },
              attr: {
                dy: "0px",
                x: function (d) {return d.cx;},
                y: function (d) {return d.cy;}
              }
            },
            {// Line #1
              textField: "text",
              classed: {text: true},
              style: {
                "font-size": "14px",
                "font-family": "Source Sans Pro, sans-serif",
                "text-anchor": "middle",
                fill: "white"
              },
              attr: {
                dy: "20px",
                x: function (d) {return d.cx;},
                y: function (d) {return d.cy;}
              }
            }
          ],
          centralFormat: [
            {// Line #0
              style: {"font-size": "50px"},
              attr: {}
            },
            {// Line #1
              style: {"font-size": "30px"},
              attr: {dy: "40px"}
            }
          ]
        }
      }]
  });
});

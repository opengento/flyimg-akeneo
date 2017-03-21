#!/usr/bin/env bash
ab -n 1000 -c 5  "http://localhost:8080/upload/w_200,h_200,c_1,rf_1/http://facedetection.jaysalvat.com/img/faces.jpg" > "var/wt_flyimg_rf_1.log"
ab -n 1000 -c 5  "http://localhost:8080/upload/w_200,h_200,c_1/http://facedetection.jaysalvat.com/img/faces.jpg" > "var/wt_flyimg_rf_0.log"

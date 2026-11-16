// CYBER ANANAS | 50 mm | 4-colour FDM keychain
// Built from clean analytic CAD geometry. No traced pixels or fake layer texture.
// part: 0 black base, 1 white CYBER, 2 turquoise, 3 gold, 4 colored preview.
part = is_undef(part) ? 4 : part;
$fn = 96;
body_z=2.8;
art_z=.64;

module stroke(points,d=.54){
    for(i=[0:len(points)-2])
        hull(){
            translate(points[i]) circle(d=d,$fn=18);
            translate(points[i+1]) circle(d=d,$fn=18);
        }
}
module arc(r,d){
    for(i=[0:179]){
        a=-18+216*i/180;
        b=-18+216*(i+1)/180;
        hull(){
            translate([r*cos(a),23+r*sin(a)]) circle(d=d,$fn=12);
            translate([r*cos(b),23+r*sin(b)]) circle(d=d,$fn=12);
        }
    }
}
module badge2d(){
    difference(){
        union(){
            translate([0,23]) circle(r=23,$fn=256);
            // Wide and structurally integrated eyelet, not a narrow neck.
            hull(){
                translate([-18.7,45.25]) circle(r=4.75,$fn=96);
                translate([-15.4,40.5]) circle(r=3.7,$fn=96);
            }
        }
        translate([-18.7,45.25]) circle(r=2.20,$fn=96);
    }
}
module fruit_outline(){
    polygon(points=[
        for(i=[0:191])
            let(a=360*i/192)
                [5.95*cos(a)*(1-.055*sin(a)),26.85+7.22*sin(a)]
    ]);
}
module diamond_grooves(){
    // Regular mathematically straight diamond lattice, no raster artifacts.
    for(a=[45,-45])
        for(k=[-9:9])
            translate([0,26.8])
                rotate(a)
                    translate([k*3.10+1.55,0])
                        square([.54,34],center=true);
}
module pictogram(kind){
    if(kind==0){ // keyhole
        circle(d=.80,$fn=20);
        translate([0,-.52]) square([.44,1.18],center=true);
    }
    if(kind==1){ // shield
        difference(){
            polygon([[-.95,.68],[.95,.68],[.75,-.52],[0,-1.03],[-.75,-.52]]);
            polygon([[-.43,.22],[.43,.22],[.32,-.30],[0,-.54],[-.32,-.30]]);
        }
    }
    if(kind==2){ // small padlock
        translate([0,-.44]) offset(r=.12) square([1.34,.95],center=true);
        difference(){
            translate([0,.35]) circle(d=1.12,$fn=20);
            translate([0,.35]) circle(d=.55,$fn=20);
            translate([0,-.18]) square([2.0,1.0],center=true);
        }
    }
    if(kind==3){ // key
        translate([-.39,.28]) circle(d=.72,$fn=18);
        stroke([[0,.10],[.56,-.49],[.84,-.49]],.30);
    }
    if(kind==4){ // circuit / tree
        stroke([[0,-.74],[0,.50]],.39);
        stroke([[0,-.12],[-.62,.42]],.33);
        stroke([[0,.12],[.63,.52]],.33);
        translate([-.62,.42]) circle(d=.44);
        translate([.63,.52]) circle(d=.44);
    }
    if(kind==5){ // power
        difference(){
            circle(d=1.32,$fn=26);
            circle(d=.66,$fn=24);
            translate([0,.72]) square([.55,.85],center=true);
        }
        stroke([[0,.75],[0,-.08]],.36);
    }
}
module security_glyphs(){
    // Deliberately designed legible motifs in selected large diamond cells.
    translate([0,30.08]) pictogram(0);
    translate([-3.0,28.65]) scale(.80) pictogram(2);
    translate([3.0,28.65]) scale(.80) pictogram(3);
    translate([0,26.77]) pictogram(4);
    translate([-3.0,24.58]) scale(.84) pictogram(1);
    translate([3.0,24.58]) scale(.82) pictogram(5);
    translate([0,22.63]) scale(.86) pictogram(0);
}
module crown(){
    polygon([[-.76,34.0],[0,43.2],[.76,34.0],[0,33.25]]);
    for(side=[-1,1]){
        polygon([[side*1.1,35.25],[side*2.50,39.1],[side*4.45,41.6],
                 [side*3.65,38.4],[side*2.05,34.5]]);
        polygon([[side*1.74,34.5],[side*3.80,37.6],[side*5.56,38.2],
                 [side*5.08,35.7],[side*2.70,33.7]]);
        polygon([[side*1.90,33.95],[side*4.13,35.4],[side*5.65,34.92],
                 [side*4.35,33.1],[side*2.95,32.75]]);
    }
}
module lock2d(){
    union(){
        translate([0,34.66]) offset(r=.25) square([2.52,1.65],center=true);
        difference(){
            translate([0,36.18]) circle(d=2.40,$fn=36);
            translate([0,36.18]) circle(d=1.17,$fn=36);
            translate([0,35.29]) square([3.2,1.9],center=true);
        }
    }
}
module lock_gold(){
    difference(){
        lock2d();
        translate([0,34.68]) circle(d=.51,$fn=20);
        translate([0,34.3]) square([.29,.60],center=true);
    }
}
module pineapple(){
    difference(){
        union(){
            difference(){
                fruit_outline();
                diamond_grooves();
                // Clean unmarked rhombuses: visible technology-inspired lattice,
                // no miniature signs that could break during 0.4 mm printing.
            }
            crown();
        }
        offset(r=.14) lock2d(); // prevents colour overlap
    }
}
module traces(){
    for(side=[-1,1])
        for(i=[0:5]){
            yy=22.1+i*2.77;
            // Keep each route inside the gold ring and away from the fruit.
            start_x=sqrt(19.8*19.8-(yy-23)*(yy-23))-.80;
            shift=(i%2==0) ? .72 : -.70;
            end_x=7.45+.2*(i%3);
            stroke([[side*start_x,yy],
                    [side*min(15.6,start_x-1.4),yy],
                    [side*min(12.4,start_x-3.0),yy+shift],
                    [side*end_x,yy+shift]],.56);
            translate([side*end_x,yy+shift]) circle(d=.9,$fn=20);
        }
}

/*
 * Purpose-drawn geometric lettering. Each character is an analytic outline,
 * built from constant-width rounded-stroke curves instead of a stock font.
 * This keeps corners clean and gives CYBER the technical horizontal look of
 * the supplied reference, and ANANAS the oblique angular letter shapes.
 */
module cyber_glyph(g){
    if(g=="C"){
        stroke([[5.24,5.48],[1.38,5.48],[.55,4.7],[.55,1.24],[1.38,.52],[5.24,.52]],1.09);
    }
    if(g=="Y"){
        stroke([[.45,5.52],[2.78,2.94],[5.17,5.52]],1.08);
        stroke([[2.78,2.94],[2.78,.50]],1.08);
    }
    if(g=="B"){
        stroke([[.52,.50],[.52,5.50],[4.45,5.50],[5.07,4.88],
                [5.07,3.78],[4.45,3.08],[.68,3.08]],1.09);
        stroke([[4.45,3.08],[5.12,2.40],[5.12,1.15],
                [4.47,.50],[.52,.50]],1.09);
    }
    if(g=="E"){
        stroke([[5.2,5.50],[.56,5.50],[.56,.50],[5.2,.50]],1.09);
        stroke([[.75,3.02],[4.75,3.02]],1.09);
    }
    if(g=="R"){
        stroke([[.50,.50],[.50,5.50],[4.38,5.50],[5.12,4.82],
                [5.12,3.71],[4.38,3.04],[.68,3.04]],1.09);
        stroke([[3.12,3.04],[5.30,.50]],1.09);
    }
}
module ananas_glyph(g){
    if(g=="A"){
        stroke([[.45,.48],[2.78,5.47],[5.12,.48]],1.25);
        stroke([[1.40,2.23],[4.18,2.23]],1.08);
    }
    if(g=="N"){
        stroke([[.49,.47],[.49,5.47],[5.08,.47],[5.08,5.47]],1.25);
    }
    if(g=="S"){
        stroke([[5.05,5.46],[1.34,5.46],[.54,4.73],[.54,3.66],
                [5.00,2.25],[5.00,1.16],[4.26,.47],[.54,.47]],1.25);
    }
}
module cyber_word(){
    difference(){
        translate([0,13.15])
            resize([38.15,5.65],auto=false)
                translate([-16.05,0])
                    for(i=[0:4])
                        translate([i*6.53,0])
                            cyber_glyph("CYBER"[i]);
        // Carefully positioned horizontal cyber cuts; no pixel-derived edges.
        for(slot=[[-16.3,6.5],[.4,6.6],[8.0,6.6],[15.35,6.0]])
            translate([slot[0],16.15])
                square([slot[1],.39],center=true);
    }
}
module ananas_word(){
    translate([0,7.65])
        resize([33.2,4.40],auto=false)
            multmatrix([[1,.15,0,0],[0,1,0,0],[0,0,1,0],[0,0,0,1]])
                translate([-17.80,0])
                    for(i=[0:5])
                        translate([i*5.95,0])
                            ananas_glyph("ANANAS"[i]);
}
module white2d(){ cyber_word(); }
module teal2d(){ arc(21.65,.95); traces(); pineapple(); ananas_word(); }
module gold2d(){ arc(20.55,.55); lock_gold(); }
module black3d(){ linear_extrude(height=body_z) badge2d(); }
module white3d(){ translate([0,0,body_z]) linear_extrude(height=art_z) white2d(); }
module teal3d(){ translate([0,0,body_z]) linear_extrude(height=art_z) teal2d(); }
module gold3d(){ translate([0,0,body_z]) linear_extrude(height=art_z) gold2d(); }
if(part==0) black3d();
if(part==1) white3d();
if(part==2) teal3d();
if(part==3) gold3d();
if(part==4){
    color([.03,.034,.04]) black3d();
    color([.95,.96,.97]) white3d();
    color([0,.80,.69]) teal3d();
    color([.89,.61,.10]) gold3d();
}
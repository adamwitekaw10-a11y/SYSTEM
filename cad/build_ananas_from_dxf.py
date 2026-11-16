#!/usr/bin/env python3
"""CYBER ANANAS: DXF -> accurate, printable 4-colour STL + assembled 3MF.
Only original vector contours are used: no bitmap tracing and no stock fonts.
"""
from __future__ import annotations

import base64
import io
import math
import os
from pathlib import Path
import xml.etree.ElementTree as ET
import zipfile

import ezdxf
from ezdxf.path import make_path
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import Polygon as MplPolygon
import numpy as np
from shapely.geometry import Polygon, MultiPolygon, GeometryCollection
from shapely.ops import unary_union
import trimesh

SOURCE = Path("cad/ananas.dxf")
OUT = Path(os.environ.get("ANANAS_OUTPUT", "out_dxf"))
OUT.mkdir(parents=True, exist_ok=True)
PCB_EXPAND = float(os.environ.get("PCB_EXPAND_MM", "0"))

# Exact DXF colours: 18 is the original black base, 4 teal, 7 white, 51 gold.
STYLES = {
    18: ("01_CZARNY", "#101317", (16,19,23,255)),
    4: ("02_ZIELONO_NIEBIESKI", "#04c9b9", (4,201,185,255)),
    7: ("03_BIALY", "#f4f5f8", (244,245,248,255)),
    51: ("04_ZLOTY", "#dcaa1d", (220,170,29,255)),
}
BASE_H = 2.70
ART_H = 0.70
CURVE_TOL = 0.016  # mm, fidelity appropriate for a 50 mm badge

doc = ezdxf.readfile(str(SOURCE))
assert doc.header.get("$INSUNITS") == 4, "DXF should declare millimetres"
entities = list(doc.modelspace())
hatches = {int(e.dxf.handle, 16): e for e in entities if e.dxftype() == "HATCH"}
shapes = {k: [] for k in STYLES}
for entity in entities:
    if entity.dxftype() not in ("SPLINE", "LWPOLYLINE"):
        continue
    source_points = [
        (p.x, p.y)
        for p in make_path(entity).flattening(distance=CURVE_TOL, segments=16)
    ]
    if len(source_points) < 3:
        raise ValueError(f"Not enough points in DXF entity {entity.dxf.handle}")
    polygon = Polygon(source_points)
    if not polygon.is_valid:
        polygon = polygon.buffer(0)
    if polygon.is_empty or polygon.area < 0.00005:
        raise ValueError(f"Invalid DXF shape {entity.dxf.handle}")
    partner = hatches.get(int(entity.dxf.handle, 16) + 1)
    if partner is None:
        raise ValueError(f"No linked HATCH colour for entity {entity.dxf.handle}")
    color_index = int(partner.dxf.color)
    if color_index not in shapes:
        raise ValueError(f"Unexpected DXF colour index {color_index}")
    shapes[color_index].append((entity.dxf.handle, polygon))

assert sum(len(v) for v in shapes.values()) == 57
print("Input contours:", {STYLES[c][0]: len(v) for c,v in shapes.items()}, flush=True)

def even_odd_region(items):
    """Resolve concentric DXF paths as outer shape / void / nested island."""
    indexed = sorted(items, key=lambda e: -e[1].area)
    depths = []
    for index, (handle, poly) in enumerate(indexed):
        dep = sum(outer.contains(poly) for _, outer in indexed[:index])
        depths.append(dep)
    print("Contour nesting:", [(indexed[i][0], depth)
                               for i,depth in enumerate(depths) if depth], flush=True)
    geom = GeometryCollection()
    for (handle, poly), depth in zip(indexed, depths):
        geom = geom.union(poly) if depth % 2 == 0 else geom.difference(poly)
    if not geom.is_valid:
        geom = geom.buffer(0)
    return geom

all_regions = {c:even_odd_region(items) for c,items in shapes.items()}
if PCB_EXPAND > 0:
    # Source B4 is the single exact vector outline containing thin PCB tracks
    # and inner cyan circuitry ring. A gentle planar offset makes those
    # hairline tracks printable with the standard 0.4 mm FDM nozzle.
    # All lettering, the pineapple diamond tiles and gold accents stay exact.
    revised=[]
    for handle, poly in shapes[4]:
        if handle=="B4":
            enlarged = poly.buffer(PCB_EXPAND, join_style=1, quad_segs=16)
            enlarged = enlarged.intersection(all_regions[18]).difference(
                all_regions[7].union(all_regions[51])
            )
            new_islands=polygons(enlarged) if "polygons" in globals() else (
                list(enlarged.geoms) if enlarged.geom_type=="MultiPolygon" else [enlarged]
            )
            revised.extend((handle+"."+str(i),g) for i,g in enumerate(new_islands))
            print("PCB B4 outline expanded by",PCB_EXPAND,"mm per side",
                  "islands",len(new_islands),"area before",round(poly.area,3),
                  "after",round(enlarged.area,3),flush=True)
        else:
            revised.append((handle,poly))
    shapes[4]=revised
    all_regions[4]=even_odd_region(shapes[4])
base = all_regions[18]
assert len(shapes[18]) == 2, "Outer silhouette and ring aperture expected"
assert base.area > 1800
print("Raw colour overlaps (mm2):",
      {f"{a}/{b}":round(all_regions[a].intersection(all_regions[b]).area,5)
       for a,b in ((4,7),(4,51),(7,51))}, flush=True)

# Prevent coplanar overlaps between differently coloured top surfaces.
white = all_regions[7].intersection(base)
gold = all_regions[51].difference(white).intersection(base)
teal = all_regions[4].difference(white.union(gold)).intersection(base)
regions = {18:base,4:teal,7:white,51:gold}

x0,y0,x1,y1=base.bounds
x_centre=(x0+x1)/2
y_bottom=y0
def translate_polygon(g):
    from shapely.affinity import translate
    return translate(g,xoff=-x_centre,yoff=-y_bottom)
regions = {c: translate_polygon(g) for c,g in regions.items()}
overall_bounds=regions[18].bounds
assert abs(overall_bounds[3]-overall_bounds[1]-50.0)<.02
assert abs(overall_bounds[2]-overall_bounds[0]-49.391)<.02
print("Dimensions (mm):",tuple(round(v,4) for v in overall_bounds),flush=True)

def polygons(geom):
    if geom.geom_type == "Polygon":
        return [geom]
    if hasattr(geom, "geoms"):
        return [p for piece in geom.geoms for p in polygons(piece)]
    return []

part_meshes={}
for color in (18,4,7,51):
    geom=regions[color]
    submeshes=[]
    # Distinct artwork islands stay as independent, manifold solid shells.
    # Unioning the long thin PCB paths to adjacent diamond cells creates
    # zero-width tangent contacts that otherwise break FDM mesh closure.
    pieces=(
        [(handle,translate_polygon(p)) for handle,p in shapes[color]]
        if color in (4,7) else
        [("merged",p) for p in polygons(geom)]
    )
    for handle,polygon in pieces:
        if polygon.area<.0001:
            continue
        m=None
        for tolerance in (0,.004,.010,.020):
            candidate=(polygon if tolerance==0 else
                polygon.simplify(tolerance,preserve_topology=True))
            if candidate.geom_type!="Polygon":
                continue
            m=trimesh.creation.extrude_polygon(
                candidate,height=BASE_H if color==18 else ART_H,
                engine="earcut"
            )
            if m.is_watertight:
                if tolerance: print("Tiny spline tolerance fix",handle,tolerance,flush=True)
                break
        if m is None or not m.is_watertight:
            raise ValueError(f"Nonwatertight DXF contour {handle} of colour {color}")
        if color != 18:
            m.apply_translation([0,0,BASE_H])
        m.remove_unreferenced_vertices()
        submeshes.append(m)
    assert submeshes, f"Empty colour {color}"
    combined=trimesh.util.concatenate(submeshes)
    if not combined.is_watertight:
        raise ValueError(f"Nonwatertight combined mesh for colour {color}")
    part_meshes[color]=combined
    filename=OUT / f"CYBER_ANANAS_DXF_{STYLES[color][0]}.stl"
    combined.export(filename,file_type="stl")
    print("STL",filename.name,"tris",len(combined.faces),
          "watertight",combined.is_watertight,
          "vol",round(combined.volume,3),"size",tuple(np.round(combined.extents,3)),flush=True)

# Single multi-part assembly with material colour resources in generic 3MF.
CORE="http://schemas.microsoft.com/3dmanufacturing/core/2015/02"
ET.register_namespace("", CORE)
def coretag(name):return f"{{{CORE}}}{name}"
model=ET.Element(coretag("model"),{"unit":"millimeter","xml:lang":"pl-PL"})
resources=ET.SubElement(model,coretag("resources"))
materials=ET.SubElement(resources,coretag("basematerials"),{"id":"1"})
ordered=(18,4,7,51)
for color in ordered:
    rgb=STYLES[color][2]
    ET.SubElement(materials,coretag("base"),{
        "name":STYLES[color][0],
        "displaycolor":"#%02X%02X%02X%02X"%rgb
    })
for idx,color in enumerate(ordered):
    mesh=part_meshes[color]
    obj=ET.SubElement(resources,coretag("object"),{
        "id":str(idx+2),"type":"model","pid":"1","pindex":str(idx)
    })
    msh=ET.SubElement(obj,coretag("mesh"))
    vertices=ET.SubElement(msh,coretag("vertices"))
    for x,y,z in mesh.vertices:
        ET.SubElement(vertices,coretag("vertex"),{
            "x":f"{x:.5f}","y":f"{y:.5f}","z":f"{z:.5f}"
        })
    triangles=ET.SubElement(msh,coretag("triangles"))
    for a,b,c in mesh.faces:
        ET.SubElement(triangles,coretag("triangle"),{
            "v1":str(int(a)),"v2":str(int(b)),"v3":str(int(c))
        })
assembly=ET.SubElement(resources,coretag("object"),{"id":"6","type":"model"})
components=ET.SubElement(assembly,coretag("components"))
for objid in (2,3,4,5):
    ET.SubElement(components,coretag("component"),{"objectid":str(objid)})
build=ET.SubElement(model,coretag("build"))
ET.SubElement(build,coretag("item"),{"objectid":"6"})
threemf=OUT / "CYBER_ANANAS_DXF_50mm_4_KOLORY.3mf"
content_types = b'''<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="model" ContentType="application/vnd.ms-package.3dmanufacturing-3dmodel+xml"/>
</Types>'''
relationships = b'''<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Target="/3D/3dmodel.model" Id="rel0" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
</Relationships>'''
with zipfile.ZipFile(threemf,"w",zipfile.ZIP_DEFLATED,compresslevel=6) as dest:
    dest.writestr("[Content_Types].xml",content_types)
    dest.writestr("_rels/.rels",relationships)
    dest.writestr("3D/3dmodel.model",ET.tostring(model,encoding="utf-8",xml_declaration=True))
with zipfile.ZipFile(threemf) as test:
    parsed=ET.fromstring(test.read("3D/3dmodel.model"))
    assert len(parsed.findall(".//"+coretag("mesh")))==4
    assert len(parsed.findall(".//"+coretag("component")))==4
print("3MF XML validated:",threemf.name,"bytes",threemf.stat().st_size,flush=True)
try:
    preview_scene=trimesh.load(str(threemf),force="scene")
    print("Independent 3MF scene import: meshes",len(preview_scene.geometry),
          "bounds",np.round(preview_scene.bounds,3),flush=True)
except Exception as exc:
    print("Independent 3MF importer warning:",repr(exc),flush=True)


fig,ax=plt.subplots(figsize=(7,7))
background="#f5f2ee"
fig.patch.set_facecolor(background);ax.set_facecolor(background)
for color in (18,4,51,7):
    for g in polygons(regions[color]):
        ax.add_patch(MplPolygon(list(g.exterior.coords),closed=True,facecolor=STYLES[color][1],linewidth=0))
        for hole in g.interiors:
            ax.add_patch(MplPolygon(list(hole.coords),closed=True,facecolor=(background if color==18 else STYLES[18][1]),linewidth=0))
ax.set_xlim(-28,28);ax.set_ylim(-3,53)
ax.set_aspect("equal");ax.axis("off")
plt.subplots_adjust(0,0,1,1)
preview=OUT/"PODGLAD_DXF_ORIGINALNE_KONTURY.png"
fig.savefig(preview,dpi=100,facecolor=background)
print("Preview:",preview.name,flush=True)
data=base64.b64encode(preview.read_bytes()).decode()
print("DXF3D_IMAGE_BEGIN")
for i in range(0,len(data),400):
    print("DXF3DCHUNK:"+data[i:i+400])
print("DXF3D_IMAGE_END")

(OUT/"INSTRUKCJA_DRUKU.txt").write_text(
    "CYBER ANANAS - BRELOK 50 mm - ORYGINALNE KONTURY DXF\n\n"
    "Model wykonany bez zmiany liter i ananasa: zachowane oryginalne kontury z ananas.dxf.\n"
    + ("Wariant pod dysze 0.4 mm: sciezki elektroniczne poszerzone o " + str(PCB_EXPAND) + " mm na strone.\n" if PCB_EXPAND>0 else "Wariant: oryginalne szerokosci sciezek z DXF.\n") +
    "Wymiary: ok. 49.39 x 50.00 x 3.40 mm.\n"
    "Kolory: czarny (korpus), zielono-niebieski, bialy, zloty.\n\n"
    "BAMBU STUDIO:\n"
    "1. Najpierw sprobuj otworzyc gotowy plik 3MF (jeden model / cztery czesci).\n"
    "2. Jesli 3MF nie przypisze prawidlowo kolorow, zaznacz wszystkie cztery STL\n"
    "   jednoczesnie przy imporcie i wybierz: jeden obiekt z wieloma czesciami.\n"
    "3. Przypisz kolory kazdej czesci z AMS.\n"
    "4. PLA, dysza 0.4 mm, warstwa 0.12 mm, druk plaszczyzna na stole, bez podpor.\n"
    "5. Najdrobniejsze szczeliny sprawdz w podgladzie po cieciu; dysza 0.2 mm\n"
    "   lepiej odwzoruje bardzo cienkie detale oryginalnego wektorowego logo.\n",
    encoding="utf-8"
)

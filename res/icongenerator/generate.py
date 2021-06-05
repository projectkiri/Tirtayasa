#=============================================================================
# Author       : Bram Ton <b.t.ton@alumnus.utwente.nl>
# Date:        : 1st of July 2011
# License      : Creative Commons Attribution-ShareAlike 3.0 Unported License
# Description  : Generate the angkot images based on the colours defined in a
#                csv file.
# Requirements : Inkscape
#                Python >= 3.2
#                Luck
# Modified by  : Pascal Alfadian Nugroho (pascalalfadian@live.com) to generate
#                based on angkot id (alphanumeric), for Kiri project
#                (https://projectkiri.id)
#=============================================================================

from xml.etree.ElementTree import ElementTree
import csv
import os
import platform
import subprocess

colours = {}
colours['purple'] = '#800080'
colours['white'] =  '#FFFFFF'
colours['black'] =  '#000000'
colours['green'] =  '#008000'
colours['lgreen'] = '#00FF00'
colours['yellow'] = '#FFFF00'
colours['blue'] =   '#0000FF'
colours['lblue'] =  '#00B0B0'
colours['red'] =    '#FF0000'
colours['dred'] =   '#AA0000'
colours['pink'] =   '#FF8080'
colours['beige'] =  '#E3E362'
colours['orange'] = '#FF6600'
colours['brown'] =  '#883300'
colours['grey'] =   '#CCCCCC'
colours['cyan'] = '#00aeef'

inkscape_path = "/usr/local/bin/inkscape"
input_path = 'input/'
base_out_path = 'out/'
base_baloon_path = 'baloon/'
base_baloon = 'baloon'

def set_colour(tree, stripe, colour):
  el = tree.find(".//{http://www.w3.org/2000/svg}rect[@id='"+stripe+"']")
  if el is None:
    el = tree.find(".//{http://www.w3.org/2000/svg}path[@id='"+stripe+"']")
    if el is None:
      print('Element not found !')

  # Get style of element
  style = el.attrib['style']

  # Unraffel style string
  d = dict()
  for prop in style.split(';'):
    tmp = prop.split(':')
    d[tmp[0]] = tmp[1]
  
  # Set colour
  d['fill'] = colours[colour]

  # Create style string
  s = '';
  for k in d.keys():
    s += k+':'+d[k]+';'
    
  # Remove last ';' from string
  s = s[:-1]

  # Set the style attrib to the new style string
  el.attrib['style'] = s
  return tree

#ElementTree.register_namespace('','http://www.w3.org/2000/svg')
#_namespace_map['http://www.w3.org/2000/svg'] = ''

def reset_directory(path):
  if not os.path.exists(path):
    os.makedirs(path)
  else:
    for the_file in os.listdir(path):
      if not the_file.startswith('.'):
        file_path = os.path.join(path, the_file)
        if os.path.isfile(file_path):
          os.unlink(file_path)

def generate_icons(track_type):
  print('Processing ' + track_type)
  
  out_path = base_out_path + track_type + '/'
  baloon_path = out_path + base_baloon_path
  icon_base = input_path + track_type + '.svg'
  icon_baloon_base = input_path + track_type + '_' + base_baloon + '.svg'
  csv_base = input_path + track_type + '.csv'

  # Generate normal icons (large and small)
  normalTree = ElementTree()
  normalTree.parse(icon_base)
  baloonTree = ElementTree()
  baloonTree.parse(icon_baloon_base)

  reset_directory(out_path)
  reset_directory(baloon_path)

  reader = csv.DictReader(open(csv_base, newline=''), delimiter=';', quotechar='"')
  for row in reader:
    name = row['angkotid']
    print('Setting colours: ' + name )

    for fieldname in reader.fieldnames:
        if fieldname != 'angkotid':
            normalTree = set_colour(normalTree, fieldname, row[fieldname])
            baloonTree = set_colour(baloonTree, fieldname, row[fieldname])

    # Save new SVG file, normal icon
    filename = out_path + name 
    normalTree.write(filename + '.svg', encoding='unicode', xml_declaration='true')
    # Convert SVG file to PNG using inkscape, normal icon
    args = [inkscape_path, filename + '.svg', "--export-filename="+filename+".png", "--export-width=50"]
    subprocess.check_call(args)
    os.remove(filename + '.svg')
  
    # Save new SVG file, baloon version
    filename = baloon_path + name 
    baloonTree.write(filename + '.svg', encoding='unicode', xml_declaration='true')
    # Convert SVG file to PNG using inkscape, baloon version
    args = [inkscape_path, filename + '.svg', "--export-filename="+filename+".png", "--export-width=50"]
    subprocess.check_call(args)
    os.remove(filename + '.svg')

# TODO to automatically scan for csv files?
generate_icons('bdo_angkot')
generate_icons('cgk_commuterline')
generate_icons('cgk_kopaja')
generate_icons('cgk_mikrolet')
generate_icons('cgk_transjakarta')
generate_icons('daytrans')
generate_icons('depok_angkot')
generate_icons('mlg_mikrolet')
generate_icons('sub_angkot')
generate_icons('xtrans')

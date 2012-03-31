#!/usr/bin/perl 
#TODO
#	РЎРґРµР»Р°С‚СЊ Р»РёР±РѕР№
#	РЎРґРµР»Р°С‚СЊ РІРѕР·РјРѕР¶РЅРѕСЃС‚СЊ РїРµС‡Р°С‚Р°С‚СЊ "РЅРµСЃС‚Р°РЅРґР°СЂС‚РЅС‹Рµ" РґРёРїР»РѕРјС‹, С‚РёРїР° СЃР°РјРѕРјСѓ СЂР°Р·Р»РѕР¶РёРІС€РµРјСѓСЃСЏ
#
#
use strict;
use warnings;
use Image::Magick;
use Getopt::Long;
use Carp;
use Data::Dumper;

my %opt;
#my $image=new Image::Magick;
#die Dumper($image->QueryFont());

GetOptions(
	'tx|text-x=i'=>\$opt{text_x},
	'ty|text-y=i'=>\$opt{text_y},
	'tw|text-width=i'=>\$opt{text_width},
	'th|text-height=i'=>\$opt{text_height},
	'x|total-x=i'=>\$opt{total_x},
	'y|total-y=i'=>\$opt{total_y},
	'o|out=s'=>\$opt{out_file},
	'i|in=s'=>\$opt{in_file},
	's|font=s'=>\$opt{font},
	'fs|font-size=i'=>\$opt{font_size},
	'fg|font-color=s'=>\$opt{font_color},
	'bg|font-backgroud=s'=>\$opt{font_bg},
	'p|place=i'=>\$opt{place},
	'pf|place-font=s'=>\$opt{place_font},
	'n|num=i'=>\$opt{num},
	'l|location=s'=>\$opt{location},
	'cat-name=s'=>\$opt{cat_name},
);
if(not $opt{text_x} or not $opt{text_y}){
	print STDERR "РќР°РґРѕ СѓРєР°Р·Р°С‚СЊ РїР°СЂР°РјРµС‚СЂС‹ СЂР°СЃРїРѕР»РѕР¶РµРЅРёСЏ С‚РµРєСЃС‚Р° (-tx|--text-x <С€РёСЂРёРЅР°> Рё -ty|--text-y <РІС‹СЃРѕС‚Р°> \n";
	print STDERR Usage();
	exit;
}
if(not $opt{text_width} or not $opt{text_height}){
	print STDERR "РќР°РґРѕ СѓРєР°Р·Р°С‚СЊ РїР°СЂР°РјРµС‚СЂС‹ Р±Р»РѕРєР° С‚РµРєСЃС‚Р° (-tw|--text-width <С€РёСЂРёРЅР°> Рё -th|--text-height <РІС‹СЃРѕС‚Р°> \n";
	print STDERR Usage();
	exit;
}
if(not $opt{in_file}){
	if(not $opt{total_x} or not $opt{total_y}){
		print STDERR "РќРµРѕР±С…РѕРґРёРјРѕ РёР»Рё СѓРєР°Р·Р°С‚СЊ РїРѕРґР»РѕР¶РєСѓ, РёР»Рё СѓРєР°Р·Р°С‚СЊ РїР°СЂР°РјРµС‚СЂС‹ СЃС‚СЂР°РЅРёС†С‹ -x Рё -y\n";
		print STDERR Usage();
		exit;
	}
}else{
	if(not -f $opt{in_file}){
		print STDERR "РќРµ РјРѕРіСѓ РїСЂРѕС‡РёС‚Р°С‚СЊ С„Р°Р№Р» РїРѕРґР»РѕР¶РєРё!\n";
		print STDERR Usage();
		exit;
	}
}
if(not $opt{out_file}){
	print STDERR "РќР°РґРѕ СѓРєР°Р·Р°С‚СЊ С„Р°Р№Р», РІ РєРѕС‚РѕСЂС‹Р№ Р±СѓРґРµС‚ Р·Р°РїРёСЃС‹РІР°С‚СЊСЃСЏ СЂРµР·СѓР»СЊС‚Р°С‚ (-o|--out <file>)\n";
	print STDERR Usage();
	exit;
}
if(not $opt{location}){
	print STDERR "РќРµРѕР±С…РѕРґРёРјРѕ СѓРєР°Р·Р°С‚СЊ РјРµСЃС‚Рѕ РїСЂРѕРІРµРґРµРЅРёСЏ (-l|--location)\n";
	print STDERR Usage();
	exit;
}
if(not $opt{place} and not length $opt{num}){
	print STDERR "РќРµРѕР±С…РѕРґРёРјРѕ СѓРєР°Р·Р°С‚СЊ Р»РёР±Рѕ Р·Р°РЅСЏС‚РѕРµ РјРµСЃС‚Рѕ (-p|--place), Р»РёР±Рѕ РЅРѕРјРµСЂ СЌРєРёРїР°Р¶Р° (-n|--num)\n";
	print STDERR Usage();
	exit;
}
if($opt{place} and $opt{num}){
	print STDERR "РќРµРѕР±С…РѕРґРёРјРѕ РІС‹Р±СЂР°С‚СЊ СѓРєР°Р·Р°Р»РёС‚СЊ РР›Р РјРµСЃС‚Рѕ РР›Р РЅРѕРјРµСЂ СЌРєРёРїР°Р¶Р°!\n";
	print STDERR Usage();
	exit;
}


$opt{font}='Arial-РџРѕР»СѓР¶РёСЂРЅС‹Р№' if not $opt{font};
$opt{font_size}=40 if not $opt{font_size};
$opt{font_color}='#000000' if not $opt{font_color} or $opt{font_color}!~/^\#[0-9A-F]{6}$/;
$opt{font_bg}='#FFFFFF' if not $opt{font_bg} or $opt{font_bg}!~/^\#[0-9A-F]{6}$/;

$opt{place_font}="Arial-Black-РћР±С‹С‡РЅС‹Р№" if not $opt{place_font};

if(not $opt{total_x} or not $opt{total_y}){ #РµСЃР»Рё РЅРµ СѓРєР°Р·Р°РЅ СЂР°Р·РјРµСЂ РєР°СЂС‚РёРЅРєРё, С‚Рѕ РґРµР»Р°РµРј РєР°СЂС‚РёРЅРєСѓ СЂР°Р·РјРµСЂРѕРј С‚РѕР»СЊРєРѕ СЃ С‚РµРєСЃС‚
	$opt{total_x}=$opt{text_x};
	$opt{total_y}=$opt{text_y};
}

my $text='';
while(<>){
	chomp;
	$text.="$_\n";
}
$text=~s/\n$//;
my $th=new Image::Magick; #text image handler
$th->Set(
	size=>"$opt{text_width}x$opt{text_height}",
);
$th->ReadImage("xc:".$opt{font_bg});
$text="\n$text";

$th->Annotate(
	font=>$opt{font},
	pointsize=>$opt{font_size},
	#fill=>$opt{font_color},
	#stroke=>$opt{font_color},
	gravity=>'Center',
	text=>$text,
);
$th->Annotate(
	font=>$opt{font},
	pointsize=>$opt{font_size}-20,
	gravity=>'South',
	text=>$opt{location},
);
if($opt{place}){
	$opt{place}=~s/1/I/;
	$opt{place}=~s/2/II/;
	$opt{place}=~s/3/III/;
	$opt{place}=~s/4/IV/;
	$opt{place}=~s/5/V/;
	$th->Annotate(
		font=>$opt{place_font},
		pointsize=>$opt{font_size}+10,
		gravity=>'North',
		text=>$opt{place}." РјРµСЃС‚Рѕ\n$opt{cat_name}",
		#	'interline-spacing'=>-100,
	);
}
else{
	$th->Annotate(
		font=>$opt{font},
		pointsize=>$opt{font_size}+50,
		gravity=>'North',
		text=>"РЅР°РіСЂР°Р¶РґР°РµС‚СЃСЏ\nСЌРєРёРїР°Р¶ в„–".$opt{num},
	);
}
my $mh=new Image::Magick; #main image handler
if(not $opt{in_file}){
	$mh->Set(
		size=>"$opt{total_x}x$opt{total_y}",
	);
	$mh->ReadImage("xc:$opt{font_bg}");
}else{
	$mh->ReadImage(filename=>$opt{in_file});
}
$mh->Composite(
	image=>$th,
	compose=>'src-in',
	x=>$opt{text_x},
	y=>$opt{text_y},
);


$mh->Write(filename=>$opt{out_file});



sub Usage
{
	my $text="\n";
	$text.="USAGE: cat nagrad_text | ./diplom.pl --text-x=INT --text-y=INT --text-width=INT --text-height=INT -i <input_file> -o <out_file>\n\n";
	$text.="OPTIONS:\n";
	$text.="	--text-x=INTEGER,--text-y=INTEGER	РєРѕРѕСЂРґРёРЅР°С‚С‹ РІРµСЂС…РЅРµРіРѕ Р»РµРІРѕРіРѕ СѓРіР»Р° РїРѕР»СЏ СЃ С‚РµРєСЃС‚РѕРј\n";
	$text.="	-tw|--text-width=INTEGER, -th|--text-height=INTEGER	РІС‹СЃРѕС‚Р° Рё С€РёСЂРёРЅР° РїРѕР»СЏ СЃ С‚РµРєСЃС‚РѕРј\n";
	$text.="	-x|--total-x=INTEGER, -y|--total-y=INTEGER	РїР°СЂР°РјРµС‚СЂС‹ РІСЃРµР№ СЃС‚СЂР°РЅРёС†С‹. РџСЂРё РёСЃРїРѕР»СЊР·РѕРІР°РЅРёРё РїРѕРґР»РѕР¶РєРё (-i) РЅРµ РЅСѓР¶РЅС‹. Р•СЃР»Рё РЅРµС‚ РїРѕРґР»РѕР¶РєРё, Рё РЅРµ СѓРєР°Р·Р°РЅС‹ СЌС‚Рё РїР°СЂР°РјРµС‚СЂС‹ СЂРёСЃСѓРµС‚СЃСЏ РїСЂРѕСЃС‚Рѕ РєР°СЂС‚РёРЅРєР° СЃ С‚РµРєСЃС‚РѕРј.\n";
	$text.="	-s|--font=STRING	РќР°Р·РІР°РЅРёРµ TTF С€СЂРёС„С‚Р°, default - Arial.\n";
	$text.="	-fs|--font-size=INTEGER	Р Р°Р·РјРµСЂ С€СЂРёС„С‚Р°, default 40.\n";
	$text.="	-fg|--font-color=STRING Р¦РІРµС‚ РЅР°РґРїРёСЃРё РІ С„РѕСЂРјР°С‚Рµ RGB. РџРѕРєР° РЅРµ СЂР°Р±РѕС‚Р°РµС‚\n";
	$text.="	-bg|--font-background=STRING	Р¤РѕРЅ РЅР°РґРїРёСЃРё РІ С„РѕСЂРјР°С‚Рµ RGB. РџСЂРё РѕС‚СЃСѓС‚СЃРІРёРё РїРѕРґР»РѕР¶РєРё СЂР°СЃРїРѕСЂСЃС‚СЂР°РЅСЏРµС‚СЃСЏ РЅР° РІСЃСЋ СЃС‚СЂР°РЅРёС†Сѓ\n";
	$text.="	-l|--location=STRING	РњРµСЃС‚Рѕ РїСЂРѕРІРµРґРµРЅРёСЏ.\n";
	$text.="	-p|--place=INTEGER	Р—Р°РЅСЏС‚РѕРµ РјРµСЃС‚Рѕ, СѓРєР°Р·С‹РІР°РµС‚СЃСЏ РІ СЃР»СѓС‡Р°Рµ РїРµС‡Р°С‚Рё РґРёРїР»РѕРјР° РїРѕР±РµРґРёС‚РµР»СЏ.\n";
	$text.="	-n|--num=INTEGER РќРѕРјРµСЂ СЌРєРёРїР°Р¶Р°, СѓРєР°Р·С‹РІР°РµС‚СЃСЏ РІ СЃР»СѓС‡Р°Рµ РїРµС‡Р°С‚Рё РґРёРїР»РѕРјР° СѓС‡Р°СЃС‚РЅРµРіР°.\n";
	$text.="	-i|--in=STRING	РїСѓС‚СЊ Рє С„Р°Р№Р»Сѓ РїРѕРґР»РѕР¶РєРё.\n";
	$text.="	-o|--out=STRING	С„Р°Р№Р», РІ РєРѕС‚РѕСЂС‹Р№ Р·Р°РїРёСЃС‹РІР°РµС‚СЃСЏ СЂРµР·СѓР»СЊС‚Р°С‚";
	$text.="\n\nEXAMPLE:\n";
	$text.="	РџСЂРёРјРµСЂ РґР»СЏ СЂРµР·РµСЂРІР°С†РёРё: test_text | ./diplom.pl  --text-x=270 --text-y=2118 --text-width=1855  --text-height=1092    -fs 120  -n   -l \"Р§РµСЂРЅРѕРіРѕР»РѕРІРєР° 2010\" -i 2010.jpg -o uch_3.jpg\n";

	$text.="\n\n";
	$text;
}

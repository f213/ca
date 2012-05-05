#!/usr/bin/perl 
#TODO
#	Сделать либой
#	Сделать возможность печатать "нестандартные" дипломы, типа самому разложившемуся
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
	print STDERR "Надо указать параметры расположения текста (-tx|--text-x <ширина> и -ty|--text-y <высота> \n";
	print STDERR Usage();
	exit;
}
if(not $opt{text_width} or not $opt{text_height}){
	print STDERR "Надо указать параметры блока текста (-tw|--text-width <ширина> и -th|--text-height <высота> \n";
	print STDERR Usage();
	exit;
}
if(not $opt{in_file}){
	if(not $opt{total_x} or not $opt{total_y}){
		print STDERR "Необходимо или указать подложку, или указать параметры страницы -x и -y\n";
		print STDERR Usage();
		exit;
	}
}else{
	if(not -f $opt{in_file}){
		print STDERR "Не могу прочитать файл подложки!\n";
		print STDERR Usage();
		exit;
	}
}
if(not $opt{out_file}){
	print STDERR "Надо указать файл, в который будет записываться результат (-o|--out <file>)\n";
	print STDERR Usage();
	exit;
}
if(not $opt{location}){
	print STDERR "Необходимо указать место проведения (-l|--location)\n";
	print STDERR Usage();
	exit;
}
if(not $opt{place} and not length $opt{num}){
	print STDERR "Необходимо указать либо занятое место (-p|--place), либо номер экипажа (-n|--num)\n";
	print STDERR Usage();
	exit;
}
if($opt{place} and $opt{num}){
	print STDERR "Необходимо выбрать указалить ИЛИ место ИЛИ номер экипажа!\n";
	print STDERR Usage();
	exit;
}


$opt{font}='Arial-Полужирный' if not $opt{font};
$opt{font_size}=40 if not $opt{font_size};
$opt{font_color}='#000000' if not $opt{font_color} or $opt{font_color}!~/^\#[0-9A-F]{6}$/;
$opt{font_bg}='#FFFFFF' if not $opt{font_bg} or $opt{font_bg}!~/^\#[0-9A-F]{6}$/;

$opt{place_font}="Arial-Black-Обычный" if not $opt{place_font};

if(not $opt{total_x} or not $opt{total_y}){ #если не указан размер картинки, то делаем картинку размером только с текст
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
		text=>$opt{place}." место\n$opt{cat_name}",
		#	'interline-spacing'=>-100,
	);
}
else{
	$th->Annotate(
		font=>$opt{font},
		pointsize=>$opt{font_size}+50,
		gravity=>'North',
		text=>"награждается\nэкипаж №".$opt{num},
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
	$text.="	--text-x=INTEGER,--text-y=INTEGER	координаты верхнего левого угла поля с текстом\n";
	$text.="	-tw|--text-width=INTEGER, -th|--text-height=INTEGER	высота и ширина поля с текстом\n";
	$text.="	-x|--total-x=INTEGER, -y|--total-y=INTEGER	параметры всей страницы. При использовании подложки (-i) не нужны. Если нет подложки, и не указаны эти параметры рисуется просто картинка с текстом.\n";
	$text.="	-s|--font=STRING	Название TTF шрифта, default - Arial.\n";
	$text.="	-fs|--font-size=INTEGER	Размер шрифта, default 40.\n";
	$text.="	-fg|--font-color=STRING Цвет надписи в формате RGB. Пока не работает\n";
	$text.="	-bg|--font-background=STRING	Фон надписи в формате RGB. При отсутсвии подложки распорстраняется на всю страницу\n";
	$text.="	-l|--location=STRING	Место проведения.\n";
	$text.="	-p|--place=INTEGER	Занятое место, указывается в случае печати диплома победителя.\n";
	$text.="	-n|--num=INTEGER Номер экипажа, указывается в случае печати диплома участнега.\n";
	$text.="	-i|--in=STRING	путь к файлу подложки.\n";
	$text.="	-o|--out=STRING	файл, в который записывается результат";
	$text.="\n\nEXAMPLE:\n";
	$text.="	Пример для резервации: test_text | ./diplom.pl  --text-x=270 --text-y=2118 --text-width=1855  --text-height=1092    -fs 120  -n   -l \"Черноголовка 2010\" -i 2010.jpg -o uch_3.jpg\n";

	$text.="\n\n";
	$text;
}

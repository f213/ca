#!/usr/bin/perl 

use strict;
use warnings;
use DBI;
use Data::Dumper;
use Carp;

my $cmd="./diplom.pl  --text-x=270 --text-y=2178 --text-width=1855  --text-height=992    -fs 120  -n %num% -l \"Р›Р•РўРћ 2011\" --total-x=2362 --total-y=3307 -o uch/%num%.jpg";

my $dbh=DBI->connect("DBI:mysql:database=orr:host=localhost","orr","BH6JV5U4UsMzQEsV");
$dbh->do("SET NAMES utf8;");

my $res=$dbh->prepare("SELECT b.start_number, a.PilotName, a.NavigatorName FROM phpbb_CA_CompRequests a, phpbb_CA_CompResults b WHERE a.comp_id=1 AND b.comp_id=1 AND a.id=b.request_id");


$res->execute;

while(my $row=$res->fetchrow_hashref){
	my $start_number=$row->{start_number};
	my $pilot=$row->{PilotName};
	my $navigator=$row->{NavigatorName};
	$pilot=~s/([^\ ]+)\ +([^\ ]+)\ +.*/$1 $2/;
	$navigator=~s/([^\ ]+)\ +([^\ ]+)\ +.*/$1 $2/;
	
	my $q=$cmd;
	$q=~s/%num%/$start_number/g;
	next if $start_number ne '100' and $start_number ne '49';
	system "echo \"$pilot\n$navigator\"|$q";
}



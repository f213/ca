#!/usr/bin/perl
#
#
use strict;
use warnings;
use Carp;
use WWW::Curl::Easy;
use Digest::MD5 qw (md5_hex);
use Time::Format qw(%time);
use JSON::XS;
use Data::Dumper;
use HTML::Template;
use Getopt::Long;

my $cat_id=4;
my $cat_name='Lite';
my $comp_id=23;
my $tpl_file='tpl/gr.tpl';
my $url="http://localhost/ca/comp-admin/results.php?comp_id=%comp_id%&f_category=%cat_id%&noauth=%no_auth%&json=1";
my $no_auth_key='chaeX6ohG2AhNg5he';

GetOptions(
	'c|cat_id=i'=>\$cat_id,
	'n|cat_name=s'=>\$cat_name,
);

my $no_auth=md5_hex($no_auth_key,$time{'yyyymmddhh'});
$url=~s/%cat_id%/$cat_id/g;
$url=~s/%no_auth%/$no_auth/g;
$url=~s/%comp_id%/$comp_id/g;

my $curl= WWW::Curl::Easy->new();
$curl->setopt(CURLOPT_WRITEFUNCTION, \&body_callback);
$curl->setopt(CURLOPT_HEADERFUNCTION, \&body_callback);
$curl->setopt(CURLOPT_URL,$url);
my @body;
$curl->setopt(CURLOPT_FILE, \@body);
if ($curl->perform() != 0) {
	confess "Error when fetching info!";
}
my $dec=JSON::XS->new;
my $data=$dec->decode(join '',@body);

my @tpl_data;
foreach my $k (sort {$a <=>$b} keys %{$data}){
	my %d=%{$data->{$k}};
	$d{pilot_name}=name($d{pilot_name_official},$d{pilot_nik});
	$d{navigator_name}=name($d{navigator_name_official},$d{navigator_nik});
	$d{place}=$d{res} if $d{res} and length $d{res};
	push @tpl_data,\%d;

}
my $template=HTML::Template->new(filename=>$tpl_file,die_on_bad_params=>0,utf8=>1);
$template->param(cat_name=>$cat_name,
		main=>\@tpl_data
);
print $template->output;

sub name
{
	local $_=shift;
	my $nick=shift;

	s/\ +([^\ ]+)\ +[^\ ]+$/\ $1/; #убираем отчество
	$_.=" ($nick)" if $nick and length $nick;
	$_;

}
sub body_callback {
    my ($chunk,$context)=@_;
    # add the chunk we received to the end of the array we've been given
    push @{$context}, $chunk;
    return length($chunk); # OK
}

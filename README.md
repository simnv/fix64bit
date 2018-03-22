# Fix 64bit Counters Cacti Plugin

Home Site: [http://docs.cacti.net/userplugin:fix64bit](http://docs.cacti.net/userplugin:fix64bit)

## Purpose

Provides a method convert 32bit interface traffic graphs to 64bit graphs by
one click on the icon near the needed graph.

Could be used to change other inputs for other graphs, but wasn't yet tested.

## Installation

Install like any other plugin, just throw it in the plugin directory,
in a folder called, for example, fix64bit.

Enable it in Plugin Management, set rights to users in User Management.

Go in Settings/Misc, verify "Fix 64bit Counters" parameters,  hit  Save  and
you are ready to go.

## Usage

After plugin install you can see icon fix64bit near the graph you can convert
(this icon will not appear near graphs that are incompatible or already converted).

Click on that icon, popup window will appear. If there are any possible problems
with conversion, you will see them in that window.

If everything is correct, confirm conversion by clicking on "Yes" button.

Also you can choose graphs to convert in the "Graph Management" page. Just select
needed graphs, choose a "Fix 64bit Counters" action in dropdown and hit "Ok".
If graphs could not be converted, you will receive corresponding message and
when you press "Continue" it will fix all the graphs that it can fix.

Fixing process is performed after the next run of poller (this is due to the need
to edit rrd file), so 64bit counters are begin to work only after two polls.

Verification is rather thorough, plugin checks SNMP version (it should be 2 or greater)
and every SNMP query constraints.

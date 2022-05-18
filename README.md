#Grade calculation set up tool

##Purpose
The aim of this tool is to
    1. provide consistent grade calculations for an organisation's Moodle site 
       where standardised grading methods exist
    2) take away the complexity of creating grade calculations from course creators.

##Usage
###Site administrator
The site admin will create grade calculation rules consisting of
    1) a table view of relevant grade item properties
    2) calculation templates using mustache markup.

###Course creator
The course creator 
   1) organises the grade items into appropriate categories within the in-built set up tool
   2) sets the visibility of the grade items by hiding/showing or setting max grade to 0/any positive number. 
      Only visible grade items are available to the Calculation setup tool.
   2) Chooses the preset rule for a grade category
   3) fills in any missing details
   4) applies the generated grade calculation.

And voila.
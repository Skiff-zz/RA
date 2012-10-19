$(document).ready(function(){
 
    $(".f-item-outer").hover(function(){
       $('#mask').show().animate({opacity: 0.8}, "slow");
    },function(){
		$('#mask').animate({opacity: 0}, "slow").hide();
	});
 
});
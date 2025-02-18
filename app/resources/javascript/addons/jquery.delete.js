// JavaScript Document
$(document).ready(function() 
					{
						//uncheck all boxes initially on pageload
						$('.chkItem').attr('checked', false);
						
						//get the initial delete link
						var iniLink = $('#deleteItem').attr('rel');
						
						//function for getting the url variables from Delete Link
						 function getUrlVars(delLink)
						 {
							var vars = [], hash;
							var hashes = delLink.slice(delLink.indexOf('?') + 1).split('&');
							for(var i = 0; i < hashes.length; i++)
							{
								hash = hashes[i].split('=');
								vars.push(hash[0]);
								vars[hash[0]] = hash[1];
							}
							return vars;
						 }
						 
						 //function for processing the link
						   $('.chkItem').click(function () 
							{         
								 var chkValue = $(this).val();
								 var id;
								 
								 if(chkValue != undefined)
								 {
								 	var allVals = chkValue;
								 	
									//check the delete url
									var delLink = $('#deleteItem').attr('rel');
									id = getUrlVars(delLink);
									
									if(id['ids'] == undefined)
									{
										//this adds the first value
								 		$('#deleteItem').attr('rel',delLink+'&ids='+allVals).addClass('confirmdeletion');
									}
									else
									{
										//this adds the remaining values
										//split the URL
										var idarray = id['ids'].split(',');
										
										if(jQuery.inArray(chkValue,idarray)=='-1')
										{
											//only adds new values only
											$('#deleteItem').attr('rel',delLink+','+allVals).addClass('confirmdeletion');
										}
										else
										{
											//removes unchecked values
											var idPos = jQuery.inArray(chkValue,idarray);
											idarray.splice(idPos,1);
											
											$('#deleteItem').attr('rel',iniLink+'&ids='+idarray).addClass('confirmdeletion');
										}
										
									}
								 }
							}						   
						   );	
						 
						//check all boxes
						 $(function () {
							 var delLink = $('#deleteItem').attr('rel');
							 
							$('.checkall').toggle(
								function() {
									$('.chkItem').attr('checked', true);
									var chkval = $('.chkItem').val();
									var chkcount = $('.chkItem').length;
									
									var ids = new Array();
									
									$('.chkItem').each(function(){
										
										ids[this.id] = $(this).val();
										//alert($(this).val());
									});
									
									//join the array with commas
									var idlist = ids.join(',');
									//alert(idlist);
								
									$('#deleteItem').attr('rel',delLink+'&ids='+idlist);
								},
								function() {
									$('.chkItem').attr('checked', false);
									
									$('#deleteItem').attr('rel',delLink);
								}
							);
						});

					});
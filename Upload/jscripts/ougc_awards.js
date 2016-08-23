/***************************************************************************
 *
 *	OUGC Awards plugin (/jscripts/ougc_awards.js)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2016 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Extend your forum with a powerful awards system.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

var OUGC_Plugins = OUGC_Plugins || {};

$.extend(true, OUGC_Plugins, {
	RequestAward: function(aid)
	{
		MyBB.popupWindow('/awards.php?action=request&modal=1&aid=' + aid);
	},
	
	submitRequest: function(uid, aid)
	{
		var datastring = $('.requestdata' + aid).serialize();
		$.ajax({
			type: 'post',
			url: 'awards.php?action=request&modal=1',
			data: datastring,
			dataType: 'html',
			success: function(data) {
				$('.modal_' + aid).fadeOut('slow', function() {
					/*$('.modal_' + aid).html(data);
					$('.modal_' + aid).fadeIn('slow');
					$('.modal').fadeIn('slow');*/
				});
				//$('.modal').fadeOut('slow');
			},
			error: function(){
				  alert(lang.unknown_error);
			}
		});

		return false;
	},

	ViewAll: function(uid)
	{
		alert('coming soon!' + uid);
	}
});
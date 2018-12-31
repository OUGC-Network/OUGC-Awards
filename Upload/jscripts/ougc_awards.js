/***************************************************************************
 *
 *	OUGC Awards plugin (/jscripts/ougc_awards.js)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2019 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Adds a powerful awards system to you community.
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
		var postData = 'action=request&modal=1&aid=' + parseInt(aid);

		MyBB.popupWindow('/awards.php?' + postData);
	},
	
	DoRequestAward: function(aid)
	{
		// Get form, serialize it and send it
		var postData = $('.request_form_' + parseInt(aid)).serialize();

		$.ajax(
		{
			type: 'post',
			dataType: 'json',
			url: 'awards.php',
			data: postData,
			success: function (request)
			{
				if(request.error)
				{
					alert(request.error);
				}
				else
				{
					$.modal.close();
					$(request.modal).appendTo('body').modal({ fadeDuration: 250}).fadeIn('slow');
				}
			},
			error: function (xhr)
			{
				location.reload(true);
			}
		});

		return false;
	},

	ViewAll: function(uid, page)
	{
		var postData = 'action=viewall&modal=1&uid=' + parseInt(uid) + '&page=' + parseInt(page);

		MyBB.popupWindow('/awards.php?' + postData);
	}
});
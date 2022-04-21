$(document).ready(function () {
    $("#formations_filter").keyup(function(){
        var filter = $(this).val()

        $('.formations section').each(function(){
            var c = 0;

            $(this).find('.f_rubrics_content article').each(function(){
                if($(this).find('h4').text().search(new RegExp(filter, "i")) < 0)
                {
                    $(this).fadeOut()
                }
                else
                {
                    $(this).fadeIn()
                    c++
                }
            })

            // On fait disparaître le titre de la rubrique
            if(c == 0)
            {
                $(this).find('.rubrics_title').fadeOut()
            }
            else
            {
                $(this).find('.rubrics_title').fadeIn()
            }
        })
    })
});
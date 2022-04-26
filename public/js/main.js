var registr = {};
var login = {};
var cart = {};

registr.opfnameModal = new jBox('Modal', {
    width: 472,
    responseiveHeight: true,
    overlay: true,
    closeOnClick: 'overlay',
    closeButton: 'box'
});

login.loginModal = new jBox('Modal', {
    title: null,
    width: 472,
    responseiveHeight: true,
    overlay: true,
    closeOnClick: 'overlay',
    closeButton: 'box',
    ajax: {
        url: '/formlogin',
        data: {},
        reload: 'strict',
        setContent: false,
        success: function (response) {
            this.setContent(response);
        },
        error: function () {
            this.setContent('<b style="color: #d33">Во время запроса произошла ошибка. Попробуйте позже.</b>');
        }
    }
});

cart.addModal = new jBox('Modal', {
    title: '',
    width: 350,
    responseiveHeight: true,
    overlay: true,
    closeOnClick: 'overlay',
    closeButton: 'box',
    content: ''
});

$(document).ready(function() {
    optionsSelect = {};
    optionsSelect.minimumResultsForSearch = Infinity;
    $('select').select2(optionsSelect);

    cart.count();

    var hash = window.location.hash.substr(1);
    if (hash == 'loginfopen') {
        login.loginModal.open();
    }

    // if ($('.orders-history').length > 0) {
    //     getOrders();
    // }

    $('.btn-login-header').click(function(e) {
        e.preventDefault();
        // login.loginModal.setContent($('#login-form-modal').html(), true);
        login.loginModal.open();
    });

    $(document).on('click', '.btn-login', function(e) {
        e.preventDefault();
        login.login($(this));
    });

    $(document).on('keyup', '#login_email', function(e) {
        if (e.which === 13) {
            $(document).find('.btn-login').click();
        }
    });

    $(document).on('keyup', '#login_pass', function(e) {
        if (e.which === 13) {
            $(document).find('.btn-login').click();
        }
    });

    $('.search-example-text').click(function(e) {
        e.preventDefault();

        var text = $(this).text();
        $(this).parents('.search').find('input').val(text);
    });

    $('.search-btn1').click(function(e) {
        e.preventDefault();

        var text = $(this).parents('.search-form').find('.search-input input').val();
        if (text.length > 2) {
            $(this).parents('.search-bl-f').find('form').submit();
        } else {
            alert('Для поиска нужно минимум 3 символа!');
        }
    });

    $('.search-btn2').click(function(e) {
        e.preventDefault();

        var text = $(this).parents('.search-form').find('.search-input input').val();
        if (text.length > 2) {
            $(this).parents('.search-bl-f').find('form').submit();
        } else {
            alert('Для поиска нужно минимум 3 символа!');
        }
    });

    // var numberedFromClass = new Numbered('.mobile_phone', {
    //     mask: '+7 (###) ###-##-##',
    //     numbered: '#',                  
    //     empty: '_',
    //     placeholder: false
    // });
    $('.mobile_phone').inputmask({"mask": "+7 (999) 999-99-99"});

    $('.product-quantity-input').inputmask({
        alias: 'numeric', 
        allowMinus: false,  
        digits: 0, 
        max: 9999
    }); 
    
    $('.type-reg').click(function(e) {
        e.preventDefault();
        $('.type-reg').removeClass('active');
        $(this).addClass('active');
        if ($(this).data('typereg') == 'ur1') {
            $('.field-cont-inn').show();
            $('.field-cont-kpp').show();
        } else if ($(this).data('typereg') == 'ur2') {
            $('.field-cont-inn').show();
            $('.field-cont-kpp').hide();
        } else {
            $('.field-cont-inn').hide();
            $('.field-cont-kpp').hide();
        }
    });

    $('.btn-reg-step1').click(function(e) {
        e.preventDefault();
        registr.step1();
    });

    $(document).on('click', '.btn-reg-step1-opfname-ok', function(e) {
        e.preventDefault();
        registr.step2();
    });

    $(document).on('click', '.btn-reg-step1-opfname-cancel', function(e) {
        e.preventDefault();
        registr.opfnameModal.close();
    });

    $(document).on('click', '.btn-reg-step2', function(e) {
        e.preventDefault();
        registr.validEmailConfirm();
    });

    $(document).on('click', '.btn-reg-step3', function(e) {
        e.preventDefault();
        registr.validPhoneConfirm();
    });

    $(document).on('click', '.repeat-sendemail-code-fake', function(e) {
        e.preventDefault();
    });

    $(document).on('click', '.repeat-sendphone-code-fake', function(e) {
        e.preventDefault();
    });

    $(document).on('click', '.repeat-sendemail-code', function(e) {
        e.preventDefault();
        registr.step2();
    });

    $(document).on('click', '.repeat-sendphone-code', function(e) {
        e.preventDefault();
        registr.step3();
    });

    $('.filter-select').change(function(e) {
        e.preventDefault();
        console.log('change');

        $(this).parents('form').submit();
    });

    $('.btn-addcart').click(function(e) {
        e.preventDefault();

        var guid = $(this).data('guid');
        var name = $(this).data('name');
        if (guid > 0) {
            var content = '<div class="product-add-cart">'
            + '<div>'
            + '    <div class="product-quantity-block">'
            + '        <a class="product-minus" href="#">-</a>'
            + '        <div><input class="product-quantity-input" name="quantity" value="1"></div>'
            + '        <a class="product-plus" href="#">+</a>'
            + '    </div>'
            + '</div>'
            + '<div>'
            + '<button class="btn-addcart-product" type="button" data-guid="' + guid + '">В корзину</button>'
            + '</div>'
            + '</div>';
            cart.addModal.setTitle(name).setContent(content).open();

            $('.product-quantity-input').inputmask({
                alias: 'numeric', 
                allowMinus: false,  
                digits: 0, 
                max: 9999
            });
        } else {
            return false;
        }
        /*if ($(this).prop('disabled')) {
            alert('Товар уже в корзине');
            return false;
        }
        var guid = $(this).data('guid');
        if (guid > 0) {
            $(this).prop('disabled', true);
            $(this).addClass('disabled');
            if ($(this).hasClass('btn-addcart-v1')) {
                $(this).text('В корзине');
            }
            var countProd = $('.cart-count').text();
            $('.cart-count').text((countProd * 1) + 1);

            cart.add(guid);
        } else {
            return false;
        }*/
    });

    $(document).on('click', '.product-del', function(e) {
        e.preventDefault();

        var parent = $(this).parents('.table-cart-item');
        parent.find('.product-quantity-input').val(0);

        $('.cart-btn-calc').click();
    });

    $(document).on('click', '.product-plus', function(e) {
        e.preventDefault();

        var parent = $(this).parents('.product-add-cart');
        var input = parent.find('.product-quantity-input');
        var inputVal = parent.find('.product-quantity-input').val();

        var newVal = (inputVal*1)+1;
        input.val(newVal);
    });

    $(document).on('click', '.product-minus', function(e) {
        e.preventDefault();

        var parent = $(this).parents('.product-add-cart');
        var input = parent.find('.product-quantity-input');
        var inputVal = parent.find('.product-quantity-input').val();

        if (inputVal*1 <= 1) {
            return false;
        }
        var newVal = (inputVal*1)-1;
        input.val(newVal);
    });

    $(document).on('click', '.btn-addcart-product', function(e) {
        e.preventDefault();
        if ($(this).prop('disabled')) {
            alert('Товар уже в корзине');
            return false;
        }
        var guid = $(this).data('guid');
        var parent = $(this).parents('.product-add-cart');
        var q = parent.find('.product-quantity-input').val();

        if (guid > 0) {
            $(this).prop('disabled', true);
            $(this).addClass('disabled');
            $(this).text('В корзине');
            var countProd = $('.cart-count').text();
            $('.cart-count').text((countProd * 1) + (q * 1));

            cart.add(guid, q);
        } else {
            return false;
        }
    });

    // Показываем loader
    $('.cart-btn-calc').click(function() {
        loaderShow();
    });
    $('.cart-btn-next').click(function() {
        loaderShow();
    });

    // $('.cart-plus').click(function(e) {
    //     e.preventDefault();

    //     var parent = $(this).parents('.table-cart-item');
    //     var quantity = parent.data('quantity');
    //     var id = parent.data('id');
    //     var input = parent.find('.cart-quantity-input');
    //     var inputVal = parent.find('.cart-quantity-input').val();

    //     if (quantity*1 <= inputVal*1) {
    //         return false;
    //     }
    //     var newVal = (inputVal*1)+1;
    //     input.val(newVal);
    // });

    // $('.cart-minus').click(function(e) {
    //     e.preventDefault();

    //     var parent = $(this).parents('.table-cart-item');
    //     var quantity = parent.data('quantity');
    //     var id = parent.data('id');
    //     var input = parent.find('.cart-quantity-input');
    //     var inputVal = parent.find('.cart-quantity-input').val();

    //     if (inputVal*1 <= 0) {
    //         return false;
    //     }
    //     var newVal = (inputVal*1)-1;
    //     input.val(newVal);
    // });

    // $('.detail-plus').click(function(e) {
    //     e.preventDefault();

    //     var parent = $(this).parents('.detail-add-cart');
    //     var quantity = parent.data('quantity');
    //     var id = parent.data('id');
    //     var input = parent.find('.detail-quantity-input');
    //     var inputVal = parent.find('.detail-quantity-input').val();

    //     // if (quantity*1 <= inputVal*1) {
    //     //     return false;
    //     // }
    //     var newVal = (inputVal*1)+1;
    //     input.val(newVal);
    // });

    // $('.detail-minus').click(function(e) {
    //     e.preventDefault();

    //     var parent = $(this).parents('.detail-add-cart');
    //     var quantity = parent.data('quantity');
    //     var id = parent.data('id');
    //     var input = parent.find('.detail-quantity-input');
    //     var inputVal = parent.find('.detail-quantity-input').val();

    //     if (inputVal*1 <= 1) {
    //         return false;
    //     }
    //     var newVal = (inputVal*1)-1;
    //     input.val(newVal);
    // });

    // $('.btn-addcart-detail').click(function(e) {
    //     e.preventDefault();
    //     if ($(this).prop('disabled')) {
    //         alert('Товар уже в корзине');
    //         return false;
    //     }
    //     var guid = $(this).data('guid');
    //     var parent = $(this).parents('.detail-add-cart');
    //     var q = parent.find('.detail-quantity-input').val();

    //     if (guid > 0) {
    //         $(this).prop('disabled', true);
    //         $(this).addClass('disabled');
    //         $(this).text('В корзине');
    //         var countProd = $('.cart-count').text();
    //         $('.cart-count').text((countProd * 1) + 1);

    //         cart.add(guid, q);
    //     } else {
    //         return false;
    //     }
    // });

    $('.shipping-type').change(function(e) {
        e.preventDefault();
        if ($(this).find('option:selected').val() == 'courier') {
            $(this).parents('.shipping-bl').find('.shipping-address-bl').show();
        } else {
            $(this).parents('.shipping-bl').find('.shipping-address-bl').hide();
        }
    });

    $('.brand-items').slick({
        dots: false,
        infinite: true,
        slidesToShow: 7,
        slidesToScroll: 7,
        speed: 300
    });
});

registr.step1 = function() {
    loaderShow();
        
    var typeRegField = $('.type-reg.active').data('typereg');
    var firstNameField = $('#first_name').val();
    var lastNameField = $('#last_name').val();
    var patronymicField = $('#patronymic_name').val();
    var emailField = $('#email').val();
    var mobileField = $('#mobile_phone').val();
    var innField = $('#inn').val();
    var kppField = $('#kpp').val();
    var passField = $('#password').val();
    var passRepeatField = $('#password_repeat').val();
    var agreeField = $('#agree').prop('checked');
    
    var dataRequest = {'ces_': $('.ces_registr').val(), 'type_reg':typeRegField, 'first_name':firstNameField, 'last_name':lastNameField, 'patronymic_name':patronymicField, 
        'email':emailField, 'mobile_phone':mobileField, 'inn':innField, 'kpp':kppField,
        'password':passField, 'password_repeat':passRepeatField, 'agree':agreeField};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/registration/step1',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        loaderHide();
        if (dataResult.error) {
            var errors = dataResult.data;
            $('.error-text').html('');
            $('.field-cont input').removeClass('error-field');

            jQuery.each(errors, function(key, value) {
                $('#'+key+'').addClass('error-field');
                $('#'+key+'').parents('.field-cont').find('.error-text').html(value);
            });
        } else if (dataResult.ok) {
            if (dataResult.type == 'ur') {
                registr.step1ConfirmOpfName(dataResult.data);
            } else {
                registr.step2();
            }
        } else {
            alert('Извините, сервис временно недоступен.');
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        loaderHide();
        alert('Извините, во время запроса произошла ошибка.');
    });
}

registr.step1ConfirmOpfName = function(data) {
    $('#opfname').html(data.opf + ' ' + data.opfname);
             
    registr.opfnameModal.setTitle('Информация по юр.лицу');
    registr.opfnameModal.setContent($('#reg-form-modal').html(), true);
    registr.opfnameModal.open();
}

registr.step2 = function() {
    registr.opfnameModal.close();
    loaderShow();
    var dataRequest = {'ces_': $('.ces_registr').val()};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/registration/sendemailcode',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        loaderHide();
        if (dataResult.ok) {
            $('#confirm-email-code').html(dataResult.email);
            registr.repeatSendEmailCodeConfirm();

            $('.block-steps .step').removeClass('active');
            $('.block-steps .step1').addClass('fihish');
            $('.block-steps .step2').addClass('active');
            $('.block-steps-text .step-text').removeClass('active');
            $('.block-steps-text .step1').addClass('fihish');
            $('.block-steps-text .step2').addClass('active');
            
            $('.block-reg-step1').hide();
            $('.block-reg-step3').hide();
            $('.block-reg-step2').show();
        } else {
            alert('Извините, сервис временно недоступен.')
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        loaderHide();
        alert('Извините, во время запроса произошла ошибка.');
    });
}

registr.validEmailConfirm = function() {
    loaderShow();
    var dataRequest = {'ces_': $('.ces_registr').val(), 'code' : $('#input-emailcode').val()};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/registration/emailcode',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        loaderHide();
        if (dataResult.ok) {
            // disabled step3
            // registr.step3();
            registr.sendReg();
        } else if (dataResult.error && dataResult.error == 'code') {
            $('#input-emailcode').addClass('error-field');
            $('#input-emailcode').parents('.field-cont').find('.error-text').html(dataResult.data);
        } else {
            alert('Извините, сервис временно недоступен.')
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        loaderHide();
        alert('Извините, во время запроса произошла ошибка.');
    });
}

registr.step3 = function() {
    loaderShow();
    var dataRequest = {'ces_': $('.ces_registr').val()};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/registration/sendphonecode',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        loaderHide();
        if (dataResult.ok) {
            $('#confirm-phone-code').html(dataResult.phone);
            registr.repeatSendPhoneCodeConfirm();

            $('.block-steps .step').removeClass('active');
            $('.block-steps .step2').addClass('fihish');
            $('.block-steps .step3').addClass('active');
            $('.block-steps-text .step-text').removeClass('active');
            $('.block-steps-text .step2').addClass('fihish');
            $('.block-steps-text .step3').addClass('active');

            $('.block-reg-step1').hide();
            $('.block-reg-step2').hide();
            $('.block-reg-step3').show();
        } else {
            alert('Извините, сервис временно недоступен.')
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        loaderHide();
        alert('Извините, во время запроса произошла ошибка.');
    });
}

registr.validPhoneConfirm = function() {
    loaderShow();
    var dataRequest = {'ces_': $('.ces_registr').val(), 'code' : $('#input-phonecode').val()};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/registration/phonecode',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        loaderHide();
        if (dataResult.ok) {
            registr.fihishReg();
        } else if (dataResult.error && dataResult.error == 'code') {
            $('#input-phonecode').addClass('error-field');
            $('#input-phonecode').parents('.field-cont').find('.error-text').html(dataResult.data);
        } else {
            alert('Извините, сервис временно недоступен.')
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        loaderHide();
        alert('Извините, во время запроса произошла ошибка.');
    });
}

registr.repeatSendEmailCodeConfirm = function() {
    $('.repeat-sendemail').html('<a href="#" class="repeat-sendemail-code-fake">Отправить повторно</a> можно будет через <span class="timer-repeat-code-email">180</span> сек.');

    var timerP = 180;
    var timerEmailID = setTimeout(function timerPhone() {
        timerP--;
        $('.timer-repeat-code-email').text(timerP);
        timerEmailID = setTimeout(timerPhone, 1000);
        if (timerP == 0) {
            $('.repeat-sendemail').html('<a href="#" class="repeat-sendemail-code">Отправить повторно</a>');
            clearTimeout(timerEmailID);
        }
    }, 1000);
}

registr.repeatSendPhoneCodeConfirm = function() {
    $('.repeat-sendphone').html('<a href="#" class="repeat-sendphone-code-fake">Отправить повторно</a> можно будет через <span class="timer-repeat-code-phone">180</span> сек.');
    
    var timerP = 180;
    var timerPhoneID = setTimeout(function timerPhone() {
        timerP--;
        $('.timer-repeat-code-phone').text(timerP);
        timerPhoneID = setTimeout(timerPhone, 1000);
    }, 1000);

    setTimeout(
        function() {
            $('.repeat-sendphone').html('<a href="#" class="repeat-sendphone-code">Отправить повторно</a>');
            clearTimeout(timerPhoneID);
        }, 180000
    );    
}

registr.sendReg = function() {
    loaderShow();
    var dataRequest = {'ces_': $('.ces_registr').val()};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/registration/sendreg',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        loaderHide();
        if (dataResult.ok) {
            registr.fihishReg();
        } else {
            alert('Извините, сервис временно недоступен.')
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        loaderHide();
        alert('Извините, во время запроса произошла ошибка.');
    });
}

registr.fihishReg = function() {
    $('.block-reg-step1').hide();
    $('.block-reg-step2').hide();
    $('.block-reg-step3').hide();

    window.location.href = "https://" + window.location.hostname + "/profile";
}

login.login = function(ths) {
    ths.prop('disabled', true);
    var parentBlock = ths.parents('.login-form-modal-block');
        
    var emailField = $('#login_email').val();
    var passField = $('#login_pass').val();
    var rememberField = $('#remember_me').prop('checked');
    
    var dataRequest = {'ces_login': $('.ces_login').val(), 'email':emailField, 'password':passField, '_remember_me':rememberField};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/login',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        if (dataResult.error) {
            parentBlock.find('.login-error-text').html(dataResult.data).show();
            ths.prop('disabled', false);
        } else if (dataResult.ok) {
            window.location.href = dataResult.data;
        } else {
            alert('Извините, сервис временно недоступен.');
            ths.prop('disabled', false);
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        ths.prop('disabled', false);
        alert('Извините, во время запроса произошла ошибка.');
    });
}

cart.add = function(product, quantity) {
    loaderShow();
    if (quantity < 1) {
        quantity = 1;
    }
    var dataRequest = {'add': 'yes', 'product': product, 'quantity': quantity};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/cartadd',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        loaderHide();
        if (!dataResult.ok) {
            alert('Извините, сервис временно недоступен.')
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        loaderHide();
        alert('Извините, во время запроса произошла ошибка.');
    });
}

cart.count = function() {
    loaderShow();
    var dataRequest = {'count': 'yes'};
    var jqxhr = $.ajax({
        type: 'POST',
        url: '/cartcount',
        data: dataRequest
    }).done(function(dataResult) {
        console.log(dataResult);
        loaderHide();
        if (dataResult.ok) {
            $('.cart-count').text(dataResult.data);
        }
    })
    .fail(function(jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        loaderHide();
    });
}

// cart.del = function(product) {
//     loaderShow();
//     var dataRequest = {'add': 'yes', 'product': product, 'quantity': quantity};
//     var jqxhr = $.ajax({
//         type: 'POST',
//         url: '/cartadd',
//         data: dataRequest
//     }).done(function(dataResult) {
//         console.log(dataResult);
//         loaderHide();
//         if (!dataResult.ok) {
//             alert('Извините, сервис временно недоступен.')
//         }
//     })
//     .fail(function(jqXHR, textStatus) {
//         console.log(jqXHR);
//         console.log(textStatus);
//         loaderHide();
//         alert('Извините, во время запроса произошла ошибка.');
//     });
// }

var loader = new jBox('Modal', {
    width: 300,
    responseiveHeight: true,
    overlay: true,
    closeOnClick: false,
    closeButton: false,
    addClass: 'loader',
    overlayClass: 'loader-overlay'
});
loader.setContent('<div class="lds-ripple"><div></div><div></div></div>', true);

function loaderShow() {
    console.log('loaderShow');
    loader.open();
}

function loaderHide() {
    loader.close();
}

// function getOrders() {
//     loaderShow();
        
//     var dataRequest = {};
//     var jqxhr = $.ajax({
//         type: 'POST',
//         url: '/profile/getorders',
//         data: dataRequest
//     }).done(function(dataResult) {
//         console.log(dataResult);
//         loaderHide();
//         if (dataResult.error) {
//             alert('Извините, во время запроса произошла ошибка.')
//         } else if (dataResult.ok) {
//             console.log(dataResult.data);

//             var listOrders = '<tr class="orders-history-cell-sort">'
//                 + '<td><a href="#">Номер заказа</a></td>'
//                 + '<td><a href="#">Дата</a></td>'
//                 + '<td><a href="#">Статус</a></td>'
//                 + '<td><a href="#">Сумма</a></td></tr>'
//                 + '<tr style="height: 10px;"><td></td></tr>';

//             $.each(dataResult.data, function(key, value) {
//                 listOrders += '<tr class="orders-history-item' + (key % 2 ? ' odd' : '') +'">'
//                 + '<td><a href="/profile/order/' + encodeURIComponent(value.orderNumber + '|' + value.orderDate) + '">' + value.orderNumber + '</a></td>'
//                 + '<td><a href="/profile/order/' + encodeURIComponent(value.orderNumber + '|' + value.orderDate) + '">' + value.orderDate + '</a></td>'
//                 + '<td><a href="/profile/order/' + encodeURIComponent(value.orderNumber + '|' + value.orderDate) + '">' + value.orderStatus + '</a></td>'
//                 + '<td><a href="/profile/order/' + encodeURIComponent(value.orderNumber + '|' + value.orderDate) + '">' + value.orderSum + '</a></td>'
//                 + '</tr>';
//             });
            
//             $('.orders-history-list').html(listOrders);

//         } else {
//             alert('Извините, сервис временно недоступен.');
//         }
//     })
//     .fail(function(jqXHR, textStatus) {
//         console.log(jqXHR);
//         console.log(textStatus);
//         loaderHide();
//         alert('Извините, во время запроса произошла ошибка.');
//     });
// }
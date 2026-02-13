<?php 

function twofactorLoad($token) {
    $html = '<input type="hidden" id="twofactor_token" name="twofactor_token" value="'.$token.'">
    <div class="relative mb-4" data-twofactor="1">
        <input type="text" id="TWOFACTORAUTHENTICATION" name="TWOFACTORAUTHENTICATION" class="block px-2.5 pb-2.5 pt-4 w-full text-sm text-white bg-transparent rounded-lg border-1 border-border-gray-300 appearance-none focus:ring-0  peer" placeholder=" " autocomplete="on">
        <label for="TWOFACTORAUTHENTICATION" class="absolute text-sm text-gray-400 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-[#09090d] px-2 peer-focus:px-2 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">2FA
            <span class="text-xs">(Two Factor Authentication)</span></label>
    </div>
    <button name="login" data-loginbutton="1" class="w-full py-2.5 px-5 mr-2 text-sm font-medium text-white bg-[#0f0f17] rounded-lg border border-[#0f0f17] hover:opacity-70 focus:z-10 focus:ring-0 focus:outline-none focus:ring-blue-700">
        <span class="inline-flex">
            Submit 2FA Code
            <svg class="w-3.5 h-3.5 ml-2 mt-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"></path>
            </svg></span>
    </button>';

    return $html;
}

?>
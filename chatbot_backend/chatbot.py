
import re

from database import get_school_knowledge
from ai_engine import generate_ai_reply


# Roman Urdu words used to identify the user's language
ROMAN_URDU_WORDS = {
    "kya", "hai", "hain", "ho", "ka", "ki", "ke",
    "ko", "mein", "main", "mujhe", "hum", "aap",
    "kaise", "kese", "kab", "kahan", "kyun",
    "nahi", "nahin", "haan", "han", "kar",
    "karta", "karti", "karte", "sakte", "sakta",
    "chahiye", "batao", "bataye", "batain",
    "karein", "karen", "krna", "krna", "kro",
    "karo", "milti", "milta", "hota", "hoti",
    "hote", "raabta", "rabta", "kitna", "kitni",
    "konsa", "konsi", "kon", "aur", "se",
    "par", "tak", "wala", "wali", "walay",
    "school", "mein", "pohanch", "pohanchna"
}


def detect_language(message):

    # Urdu script detection
    if re.search(r"[\u0600-\u06FF]", message):
        return "Urdu"

    words = set(re.findall(r"[a-z]+", message.lower()))

    roman_matches = words.intersection(ROMAN_URDU_WORDS)

    if len(roman_matches) >= 1:
        return "Roman Urdu"

    return "English"


def normalize_word(word):

    if word.endswith("ies") and len(word) > 4:
        return word[:-3] + "y"

    if word.endswith("s") and len(word) > 3:
        return word[:-1]

    return word


def get_words(value):

    words = set(re.findall(r"[a-z]+", value.lower()))

    words = {
        normalize_word(word)
        for word in words
    }

    ignored = {
        "what", "is", "the", "a", "an", "how",
        "can", "i", "do", "you", "please",
        "tell", "me", "about", "are", "of",
        "when", "does", "did", "where", "which",
        "who", "why", "to", "from", "in", "on",
        "at", "and", "or", "my", "your", "their",
        "this", "that", "there", "it", "for",
        "kya", "hai", "hain", "ka", "ki", "ke",
        "ko", "mein", "main", "mujhe", "kaise",
        "kese", "bataye", "batao", "par", "se",
        "aur", "ho", "aap"
    }

    return words - ignored


def get_chatbot_reply(message):

    text = message.lower().strip()

    if not text:
        return "Please type your question."

    # Detect user's language
    language = detect_language(message)

    # Greetings
    greeting_words = {
        "hello", "hi", "hey", "salam",
        "assalam", "aoa", "assalamualaikum"
    }

    if any(word in text.split() for word in greeting_words):

        if language == "Roman Urdu":
            return (
                "Assalam-o-Alaikum! DAAES School mein khush aamdeed. "
                "Main aapki kya madad kar sakta hoon?"
            )

        if language == "Urdu":
            return (
                "السلام علیکم! DAAES School میں خوش آمدید۔ "
                "میں آپ کی کیا مدد کر سکتا ہوں؟"
            )

        return (
            "Assalam-o-Alaikum! Welcome to DAAES School. "
            "How can I help you today?"
        )

    # Thank you
    if "thank" in text or "shukriya" in text:

        if language == "Roman Urdu":
            return "Aapka khair maqdam hai! Mazeed koi sawal ho to pooch sakte hain."

        if language == "Urdu":
            return "آپ کا خیر مقدم ہے! مزید کوئی سوال ہو تو پوچھ سکتے ہیں۔"

        return "You're welcome! Feel free to ask if you need any help."

    # Get school information from database
    school_data = get_school_knowledge()

    best_score = 0
    best_answer = None

    if school_data:

        lines = school_data.split("\n\n")

        user_words = get_words(text)

        # Match FAQs only in the detected language
        for item in lines:

            question = ""
            answer = ""
            keywords = ""
            faq_language = ""

            for line in item.split("\n"):

                if line.startswith("Question: "):
                    question = line.replace("Question: ", "", 1)

                elif line.startswith("Answer: "):
                    answer = line.replace("Answer: ", "", 1)

                elif line.startswith("Keywords: "):
                    keywords = line.replace("Keywords: ", "", 1)

                elif line.startswith("Language: "):
                    faq_language = line.replace("Language: ", "", 1).strip()

            # Ignore records without a language field
            if not question or not answer or not faq_language:
                continue

            # Only compare FAQs in the user's language
            if faq_language.lower() != language.lower():
                continue

            question_words = get_words(question)
            keyword_words = get_words(keywords)

            question_matches = user_words.intersection(question_words)
            keyword_matches = user_words.intersection(keyword_words)

            score = (
                len(question_matches) * 3
                + len(keyword_matches) * 2
            )

            # Prioritize teacher vs student attendance
            if "teacher" in user_words or "teachers" in user_words:
                if "teacher" in question_words or "teacher" in keyword_words:
                    score += 10

                elif "student" in question_words or "student" in keyword_words:
                    score -= 10

            if "student" in user_words or "students" in user_words:
                if "student" in question_words or "student" in keyword_words:
                    score += 10

                elif "teacher" in question_words or "teacher" in keyword_words:
                    score -= 10

            # Select the best matching FAQ
            if score > best_score:
                best_score = score
                best_answer = answer

        if best_score >= 4 and best_answer:
            return best_answer

    # AI fallback with language instruction
    if language == "Roman Urdu":

        ai_message = (
            "IMPORTANT: Reply only in Roman Urdu. "
            "Do not reply in English or Urdu script. "
            "Use the school information provided below.\n\n"
            "User question: " + message
        )

    elif language == "Urdu":

        ai_message = (
            "IMPORTANT: Reply only in Urdu script. "
            "Use the school information provided below.\n\n"
            "User question: " + message
        )

    else:

        ai_message = (
            "IMPORTANT: Reply only in English. "
            "Use the school information provided below.\n\n"
            "User question: " + message
        )

    ai_reply = generate_ai_reply(ai_message, school_data)

    if ai_reply:
        return ai_reply

    # Default response
    if language == "Roman Urdu":
        return (
            "Main DAAES School Assistant hoon. "
            "School se related sawalat pooch sakte hain. "
            "Mazeed maloomat ke liye school administration se rabta karein."
        )

    if language == "Urdu":
        return (
            "میں DAAES School Assistant ہوں۔ "
            "اسکول سے متعلق سوالات پوچھ سکتے ہیں۔ "
            "مزید معلومات کے لیے اسکول انتظامیہ سے رابطہ کریں۔"
        )

    return (
        "I'm the DAAES School Assistant. I can help with general "
        "school-related questions. For specific information, "
        "please contact the school administration."
    )

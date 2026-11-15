
from config import Config


def generate_ai_reply(message, school_data=None):

    api_key = Config.OPENAI_API_KEY

    # AI is optional. Without an API key, use the basic FAQ responses.
    if not api_key:
        return None

    try:
        from openai import OpenAI

        client = OpenAI(api_key=api_key)

        context = ""

        if school_data:
            context = f"\nSchool information:\n{school_data}"

        response = client.responses.create(
            model=Config.OPENAI_MODEL,
            instructions=(
                "You are the DAAES School Assistant. "
                "Answer politely and clearly. "
                "Only give school-specific facts when they are present "
                "in the supplied school information. "
                "If you do not know an answer, direct the visitor "
                "to the school administration."
                + context
            ),
            input=message
        )

        return response.output_text

    except Exception as error:
        print("AI Error:", error)
        return None
